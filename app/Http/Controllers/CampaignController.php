<?php

namespace App\Http\Controllers;

use App\Http\Requests\Communication\CampaignRequest;
use App\Http\Requests\Communication\DeliveryActionRequest;
use App\Models\Campaign;
use App\Models\CommunicationAudience;
use App\Models\CommunicationTemplate;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Setting;
use App\Services\CampaignService;
use App\Services\MessageTemplateService;
use App\Services\NotificationPreferenceService;
use App\Services\SmsGatewayService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = Campaign::query()->withCount('deliveries')->latest()->paginate(20);

        return view('modules.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        return $this->form(new Campaign(['channels' => ['sms'], 'content' => [], 'audience_filters' => request('router') ? ['router_id' => request('router')] : [], 'audience_mode' => 'snapshot', 'purpose' => 'service']));
    }

    public function edit(Campaign $campaign): View
    {
        abort_unless($campaign->status === 'draft', 422);

        return $this->form($campaign);
    }

    private function form(Campaign $campaign): View
    {
        return view('modules.campaigns.form', ['campaign' => $campaign, 'templates' => CommunicationTemplate::query()->whereNull('event_key')->where('enabled', true)->pluck('name', 'id'), 'audiences' => CommunicationAudience::query()->orderBy('name')->get(), 'routers' => Router::query()->pluck('name', 'id'), 'plans' => Plan::query()->pluck('name', 'id')]);
    }

    public function store(CampaignRequest $request, CampaignService $service): RedirectResponse
    {
        $campaign = new Campaign(['tenant_id' => tenant()->id, 'created_by' => $request->user()->id]);
        $this->save($request, $campaign, $service);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Draft saved. Review recipients and message before sending.');
    }

    public function update(CampaignRequest $request, Campaign $campaign, CampaignService $service): RedirectResponse
    {
        abort_unless($campaign->status === 'draft', 422);
        $this->save($request, $campaign, $service);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Draft updated.');
    }

    private function save(CampaignRequest $request, Campaign $campaign, CampaignService $service): void
    {
        $data = $request->validated();
        $content = $request->only(['subject', 'email_body', 'sms_body']);
        if ($request->filled('template_id')) {
            $content = CommunicationTemplate::query()->findOrFail($data['template_id'])->only(['subject', 'email_body', 'sms_body', 'sms_mode', 'lookup_template', 'lookup_tokens', 'version']);
        }
        app(MessageTemplateService::class)->validate($content);
        foreach ($data['channels'] as $channel) {
            if (($channel === 'email' && (blank($content['email_body'] ?? null) || blank($content['subject'] ?? null))) || ($channel === 'sms' && blank($content['sms_body'] ?? null) && ($content['sms_mode'] ?? 'plain') !== 'lookup')) {
                throw ValidationException::withMessages([$channel.'_body' => 'Provide content for every selected channel.']);
            }
        }
        $filters = $data['filters'];
        $campaign->fill(Arr::only($data, ['name', 'purpose', 'channels', 'audience_mode']));
        $campaign->fill(['content' => $content, 'audience_filters' => $filters, 'recipient_ids' => $service->audience($filters)->pluck('id')->all()])->save();
        if ($request->filled('save_audience_name')) {
            CommunicationAudience::query()->updateOrCreate(['tenant_id' => tenant()->id, 'name' => $data['save_audience_name']], ['filters' => $filters]);
        }
    }

    public function show(Campaign $campaign, CampaignService $service): View
    {
        $filters = $campaign->audience_mode === 'snapshot' ? ['customer_ids' => $campaign->recipient_ids ?: [-1]] : $campaign->audience_filters;
        $preview = $campaign->status === 'draft' ? $service->preview($filters, $campaign->channels, $campaign->content) : null;
        $stats = $campaign->deliveries()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $deliveries = $campaign->deliveries()->latest()->paginate(30);

        return view('modules.campaigns.show', compact('campaign', 'preview', 'stats', 'deliveries'));
    }

    public function action(DeliveryActionRequest $request, Campaign $campaign): RedirectResponse
    {
        $action = $request->validated('action');
        Campaign::resolveConnection()->transaction(function () use ($request, $campaign, $action): void {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (in_array($action, ['send', 'schedule'], true)) {
                abort_unless($campaign->status === 'draft', 422);
                $channels = app(NotificationPreferenceService::class)->tenantSettings(tenant()->id)['channels'];
                foreach ($campaign->channels as $channel) {
                    if (! ($channels[$channel] ?? false)) {
                        throw ValidationException::withMessages(['channels' => 'Enable '.$channel.' in Settings → Notifications before sending.']);
                    }
                }
                if (in_array('sms', $campaign->channels, true) && app(SmsGatewayService::class)->settings(tenant()->id)['provider'] === 'none') {
                    throw ValidationException::withMessages(['channels' => 'Configure an active SMS gateway before sending.']);
                }
                if (in_array('email', $campaign->channels, true) && ! (Setting::get('email.outgoing', [], tenant()->id)['active'] ?? false)) {
                    throw ValidationException::withMessages(['channels' => 'Configure active outgoing email before sending.']);
                }
                if ($campaign->audience_mode === 'snapshot' && empty($campaign->recipient_ids)) {
                    throw ValidationException::withMessages(['audience' => 'Select at least one customer before sending.']);
                }
                $scheduled = $action === 'schedule' ? Carbon::parse($request->input('scheduled_at'), tenant()->timezone ?? 'UTC')->utc() : now();
                if ($action === 'schedule' && $scheduled->lte(now())) {
                    throw ValidationException::withMessages(['scheduled_at' => 'Choose a future time.']);
                }
                $campaign->update(['status' => 'scheduled', 'scheduled_at' => $scheduled]);
            } elseif ($action === 'pause') {
                abort_unless(in_array($campaign->status, ['scheduled', 'sending'], true), 422);
                $campaign->update(['status' => 'paused']);
            } elseif ($action === 'resume') {
                abort_unless($campaign->status === 'paused', 422);
                $campaign->update(['status' => $campaign->materialized_at ? 'sending' : 'scheduled']);
            } elseif ($action === 'cancel') {
                abort_unless(in_array($campaign->status, ['draft', 'scheduled', 'sending', 'paused'], true), 422);
                $campaign->update(['status' => 'cancelled']);
                $campaign->deliveries()->whereIn('status', ['pending', 'retrying'])->update(['status' => 'cancelled']);
            } elseif ($action === 'retry') {
                abort_unless(in_array($campaign->status, ['completed', 'completed_with_failures', 'sending'], true), 422);
                $campaign->deliveries()->where('status', 'failed')->where('attempts', '<', 6)->update(['status' => 'pending', 'available_at' => now(), 'expires_at' => now()->addDay()]);
                $campaign->update(['status' => 'sending', 'completed_at' => null]);
            } elseif ($action === 'duplicate') {
                $copy = $campaign->replicate(['status', 'scheduled_at', 'materialized_at', 'completed_at']);
                $copy->fill(['name' => $campaign->name.' (copy)', 'status' => 'draft', 'created_by' => $request->user()->id])->save();
            } else {
                abort(422);
            }
        });

        return back()->with('success', 'Campaign updated. Pending work is processed by the communication scheduler.');
    }
}
