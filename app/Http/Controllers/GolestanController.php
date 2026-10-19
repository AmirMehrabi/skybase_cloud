<?php

namespace App\Http\Controllers;

use App\Http\Requests\GolestanSearchRequest;
use App\Services\GolestanService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class GolestanController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $this->page();
    }

    public function search(GolestanSearchRequest $request, GolestanService $service): Response
    {
        $result = null;
        $error = null;
        try {
            $result = $service->search($request->validated('student_number'), $request->validated('username'), $request->validated('password'));
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }

        return $this->page([
            'studentNumber' => $request->validated('student_number'),
            'username' => $request->validated('username'),
            'requestXml' => $service->requestXml($request->validated('student_number'), $request->validated('username'), '[REDACTED]'),
            'result' => $result,
            'serviceError' => $error,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function page(array $data = []): Response
    {
        return response()->view('modules.golestan.index', array_merge([
            'studentNumber' => '', 'username' => '', 'result' => null, 'serviceError' => null, 'requestXml' => null,
        ], $data))->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
