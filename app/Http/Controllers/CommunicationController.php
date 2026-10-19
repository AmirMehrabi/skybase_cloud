<?php

namespace App\Http\Controllers;

use App\Http\Requests\Communication\DeliveryActionRequest;
use App\Http\Requests\Communication\IncidentPolicyRequest;
use App\Http\Requests\Communication\TemplateRequest;
use App\Models\CommunicationTemplate;
use App\Models\MessageDelivery;
use App\Models\NotificationIncident;
use App\Models\Setting;
use App\Services\CommunicationDeliveryService;
use App\Services\MessageTemplateService;
use App\Services\NotificationAutomationService;
use App\Services\NotificationIncidentService;
use App\Support\Notifications\NotificationEventRegistry;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommunicationController extends Controller
{
    public function index(): View
    {
        app(NotificationAutomationService::class)->ensureDefaults(tenant()->id);

        return view('modules.communications.index', ['templates' => CommunicationTemplate::query()->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return $this->form(new CommunicationTemplate(['channels' => ['email'], 'enabled' => true, 'sms_mode' => 'plain', 'recipient_type' => 'customer', 'lookup_tokens' => []]));
    }

    public function edit(CommunicationTemplate $template): View
    {
        return $this->form($template);
    }

    private function form(CommunicationTemplate $template): View
    {
        return view('modules.communications.template', ['template' => $template, 'events' => array_map(fn ($event) => $event['label'], NotificationEventRegistry::events())]);
    }

    public function store(TemplateRequest $request): RedirectResponse
    {
        return $this->save($request, new CommunicationTemplate(['tenant_id' => tenant()->id]));
    }

    public function update(TemplateRequest $request, CommunicationTemplate $template): RedirectResponse
    {
        return $this->save($request, $template);
    }

    private function save(TemplateRequest $request, CommunicationTemplate $template): RedirectResponse
    {
        $data = $request->safe()->except('recipient_lines');
        app(MessageTemplateService::class)->validate($data);
        foreach ($data['channels'] as $channel) {
            if (($channel === 'email' && (blank($data['subject'] ?? null) || blank($data['email_body'] ?? null))) || ($channel === 'sms' && $data['sms_mode'] === 'plain' && blank($data['sms_body'] ?? null))) {
                throw ValidationException::withMessages([$channel.'_body' => 'Enter content for the selected channel.']);
            }
        }
        if ($data['sms_mode'] === 'lookup' && empty($data['lookup_tokens']['token'])) {
            throw ValidationException::withMessages(['lookup_tokens' => 'Map the primary Lookup token.']);
        }
        foreach ($data['recipients'] as $recipient) {
            $valid = $recipient['channel'] === 'email' ? filter_var($recipient['destination'], FILTER_VALIDATE_EMAIL) : preg_match('/^\+?[0-9]{10,15}$/', $recipient['destination']);
            if (! $valid) {
                throw ValidationException::withMessages(['recipient_lines' => 'One or more incident contact destinations are invalid.']);
            }
        }
        $template->fill($data)->fill(['version' => $template->exists ? $template->version + 1 : 1])->save();

        return redirect()->route('communications.index')->with('success', 'Template saved. Already queued messages keep their original content.');
    }

    public function deliveries(Request $request): View
    {
        $deliveries = MessageDelivery::query()->with('campaign')->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('channel'), fn ($query) => $query->where('channel', $request->input('channel')))
            ->when($request->filled('event_key'), fn ($query) => $query->where('event_key', $request->input('event_key')))->latest()->paginate(30)->withQueryString();

        return view('modules.communications.deliveries', compact('deliveries'));
    }

    public function retry(DeliveryActionRequest $request, MessageDelivery $delivery): RedirectResponse
    {
        abort_unless($request->input('action') === 'retry' && $delivery->status === 'failed' && $delivery->attempts < 6, 422);
        $delivery->update(['status' => 'pending', 'available_at' => now(), 'expires_at' => now()->addDay()]);

        return back()->with('success', 'Failed delivery queued for another attempt.');
    }

    public function test(DeliveryActionRequest $request, CommunicationTemplate $template): RedirectResponse
    {
        $channel = $request->input('channel');
        $destination = $request->input('destination');
        abort_unless(in_array($channel, $template->channels, true) && filled($destination), 422);
        $valid = $channel === 'email' ? filter_var($destination, FILTER_VALIDATE_EMAIL) : preg_match('/^\+?[0-9]{10,15}$/', $destination);
        abort_unless($valid, 422, 'Enter a valid test destination.');
        $data = array_fill_keys(MessageTemplateService::VARIABLES, 'Sample');
        $content = app(MessageTemplateService::class)->render($template->only(['subject', 'email_body', 'sms_body', 'sms_mode', 'lookup_template', 'lookup_tokens']), $data);
        app(CommunicationDeliveryService::class)->create(tenant()->id, 'template-test:'.Str::uuid(), ['channel' => $channel, 'destination' => $destination, 'content' => $content, 'expires_at' => now()->addMinutes(15)]);

        return back()->with('success', 'Real test message queued. See Delivery activity for the result.');
    }

    public function incidents(): View
    {
        return view('modules.communications.incidents', ['incidents' => NotificationIncident::query()->with('router')->where('status', '!=', 'observing')->latest()->paginate(20), 'policy' => app(NotificationIncidentService::class)->policy(tenant()->id)]);
    }

    public function policy(IncidentPolicyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['enabled'] = $request->boolean('enabled');
        $data['escalation_minutes'] = collect(explode(',', $data['escalation_minutes'] ?? ''))->map(fn ($value) => (int) trim($value))->filter(fn ($value) => $value > 0)->unique()->sort()->take(5)->values()->all();
        $data['maintenance_until'] = $request->filled('maintenance_until') ? Carbon::parse($request->input('maintenance_until'), tenant()->timezone ?? 'UTC')->utc()->toIso8601String() : null;
        Setting::query()->updateOrCreate(['tenant_id' => tenant()->id, 'key' => 'notifications.incident_policy'], ['value' => $data, 'type' => 'json', 'group' => 'notifications']);

        return back()->with('success', 'Incident policy saved.');
    }

    public function incidentAction(DeliveryActionRequest $request, NotificationIncident $incident): RedirectResponse
    {
        abort_unless($incident->status === 'open', 422);
        if ($request->input('action') === 'acknowledge') {
            $incident->update(['acknowledged_at' => now(), 'acknowledged_by' => $request->user()->id]);
        } elseif ($request->input('action') === 'resolve') {
            $incident->update(['status' => 'resolved', 'resolved_at' => now()]);
            app(NotificationIncidentService::class)->dispatch($incident, true);
        } else {
            abort(422);
        }

        return back()->with('success', 'Incident updated. Obsolete queued escalations will be skipped.');
    }
}
