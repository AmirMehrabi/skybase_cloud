<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GolestanService
{
    public function requestXml(string $studentNumber, bool $redactPassword = false): string
    {
        $public = new DOMDocument;
        $root = $public->appendChild($public->createElement('Root'));
        foreach ([1, 2, 4, 6, 7, 8, 10, 18, 20, 22, 24, 26, 27, 29, 30, 32, 34, 36, 37, 38, 40, 42, 48, 101, 102, 103] as $id) {
            $node = $root->appendChild($public->createElement('N'));
            $node->setAttribute('id', (string) $id);
            $attributes = [
                'fs' => '1', 'ft' => '7',
                'F1' => $id === 4 ? $studentNumber : '', 'T1' => $id === 4 ? $studentNumber : '',
                'F2' => '', 'T2' => '', 'B' => ' ', 'Q' => '0', 'A' => ' ',
                'S' => in_array($id, [18, 20, 22, 24, 26], true) ? '0' : ' ',
            ];
            foreach ($attributes as $attribute => $value) {
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
            'pri' => '<Root><N UQID="245" id="5" fs="0" ft="7" F="2" T="2"/><N UQID="10" id="10" fs="0" ft="7" F="" T=""/><N UQID="153" id="20" fs="0" ft="7" F="" T=""/><N UQID="17450" id="24" fs="0" ft="7" F="" T=""/></Root>',
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

    /** @return array{records: array<int, array<string, string>>, xml: string} */
    public function search(string $studentNumber): array
    {
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
                ->withBody($this->requestXml($studentNumber), 'text/xml; charset=utf-8')
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
            if (($record['STDNO'] ?? null) === $studentNumber) {
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
