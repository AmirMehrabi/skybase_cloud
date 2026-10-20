<?php

namespace App\Services;

use App\Support\GolestanFilters;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GolestanService
{
    /** @param array<string, string|null>|string $filters */
    public function requestXml(array|string $filters, bool $redactPassword = false): string
    {
        $filters = is_string($filters) ? ['student_number' => $filters] : $filters;
        $ranges = [];
        foreach (GolestanFilters::fields() as $name => $field) {
            if ($name !== 'academic_term') {
                $ranges[$field['id']] = GolestanFilters::range($filters, $name);
            }
        }
        $public = new DOMDocument;
        $root = $public->appendChild($public->createElement('Root'));
        foreach ([1, 2, 3, 4, 6, 7, 8, 10, 18, 20, 22, 24, 26, 27, 29, 30, 32, 34, 36, 37, 38, 40, 42, 48, 101, 102, 103] as $id) {
            if ($id === 3 && $ranges[3] === ['', '']) {
                continue;
            }
            $node = $root->appendChild($public->createElement('N'));
            $node->setAttribute('id', (string) $id);
            $attributes = [
                'fs' => '1', 'ft' => '7',
                'F1' => $ranges[$id][0], 'T1' => $ranges[$id][1],
                'F2' => '', 'T2' => '', 'B' => ' ', 'Q' => '0', 'A' => ' ',
                'S' => in_array($id, [18, 20, 22, 24, 26], true) ? '0' : ' ',
            ];
            foreach ($attributes as $attribute => $value) {
                $node->setAttribute($attribute, $value);
            }
        }

        [$termFrom, $termTo] = GolestanFilters::range($filters, 'academic_term');
        $private = new DOMDocument;
        $privateRoot = $private->appendChild($private->createElement('Root'));
        foreach ([5 => ['245', '2', '2'], 10 => ['10', $termFrom, $termTo], 20 => ['153', '', ''], 24 => ['17450', '', '']] as $id => [$uqid, $from, $to]) {
            $node = $privateRoot->appendChild($private->createElement('N'));
            foreach (['UQID' => $uqid, 'id' => (string) $id, 'fs' => '0', 'ft' => '7', 'F' => $from, 'T' => $to] as $attribute => $value) {
                $node->setAttribute($attribute, $value);
            }
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $envelope = $document->appendChild($document->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Envelope'));
        $body = $envelope->appendChild($document->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Body'));
        $operation = $body->appendChild($document->createElementNS('urn:nowpardaz', 'golInfo'));
        $parameters = [
            'login' => (string) config('services.golestan.username'),
            'pass' => $redactPassword ? '[REDACTED]' : (string) config('services.golestan.password'),
            'sec' => 'AA0ECD9901',
            'pub' => $public->saveXML($root),
            'pri' => $private->saveXML($privateRoot),
            'mor' => '<Root/>',
            'iFID' => '1132',
        ];
        foreach ($parameters as $name => $value) {
            $element = $operation->appendChild($document->createElementNS('urn:nowpardaz', $name));
            $element->appendChild($document->createTextNode($value));
        }

        $document->formatOutput = true;

        return $document->saveXML();
    }

    /**
     * @param  array<string, string|null>|string  $filters
     * @return array{records: array<int, array<string, string>>, xml: string}
     */
    public function search(array|string $filters): array
    {
        $filters = is_string($filters) ? ['student_number' => $filters] : $filters;
        if (! config('services.golestan.enabled')) {
            throw new RuntimeException('Golestan integration is disabled.');
        }
        if (blank(config('services.golestan.username')) || blank(config('services.golestan.password'))) {
            throw new RuntimeException('Golestan web-service credentials are not configured.');
        }

        try {
            $response = Http::connectTimeout(10)->timeout(40)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['SOAPAction' => '"urn:nowpardaz/golInfo"'])
                ->withBody($this->requestXml($filters), 'text/xml; charset=utf-8')
                ->post('https://eduold.uk.ac.ir/GolestanService/gservice.asmx');
        } catch (ConnectionException) {
            throw new RuntimeException('Golestan could not be reached. Please try again.');
        }

        $xml = $this->parseXml($response->body());
        $xpath = new DOMXPath($xml);
        if ($xpath->query('//*[local-name()="Fault"]')->length > 0 || ! $response->successful()) {
            throw new RuntimeException('Golestan rejected the request. Check your web-service credentials and report access.');
        }
        $result = $xpath->query('//*[local-name()="golInfoResult"]')->item(0);
        if (! $result instanceof DOMElement) {
            throw new RuntimeException('Golestan returned an unexpected response.');
        }
        if ($result->childElementCount === 0 && str_starts_with(trim($result->textContent), '<')) {
            $xml = $this->parseXml(trim($result->textContent));
            $result = $xml->documentElement;
        }

        $records = [];
        foreach ((new DOMXPath($xml))->query('//*[@STDNO] | //*[./*[local-name()="STDNO"]]') as $row) {
            $record = [];
            foreach ($row->attributes as $attribute) {
                $record[$attribute->name] = $attribute->value;
            }
            foreach ($row->childNodes as $field) {
                if ($field instanceof DOMElement) {
                    $record[$field->localName] = trim($field->textContent);
                }
            }
            [$from, $to] = GolestanFilters::range($filters, 'student_number');
            $number = $record['STDNO'] ?? '';
            if (($from === '' || GolestanFilters::compareNumbers($number, $from) >= 0)
                && ($to === '' || GolestanFilters::compareNumbers($number, $to) <= 0)) {
                $records[] = $record;
            }
        }

        $messages = (new DOMXPath($xml))->query('//*[local-name()="P"]');
        if ($records === [] && $messages->length > 0 && trim($messages->item(0)->textContent) !== '') {
            throw new RuntimeException('Golestan: '.mb_substr(trim($messages->item(0)->textContent), 0, 500));
        }

        if ($records === [] && trim($result->textContent) !== '' && $result->childElementCount === 0) {
            throw new RuntimeException('Golestan returned a service message instead of student data. Check your credentials and report access.');
        }

        return ['records' => $records, 'xml' => $xml->saveXML($result)];
    }

    private function parseXml(string $content): DOMDocument
    {
        if (stripos($content, '<!DOCTYPE') !== false || stripos($content, '<!ENTITY') !== false) {
            throw new RuntimeException('Golestan returned an unsupported XML response.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument;
            if (! $document->loadXML($content, LIBXML_NONET)) {
                throw new RuntimeException('Golestan returned an invalid XML response.');
            }

            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
