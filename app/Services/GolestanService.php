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
    /** @return array{records: array<int, array<string, string>>, xml: string} */
    public function search(string $studentNumber, string $username, #[\SensitiveParameter] string $password): array
    {
        $public = new DOMDocument;
        $root = $public->appendChild($public->createElement('Root'));
        foreach ([1, 2, 3, 4, 6, 7, 8, 10, 18, 20, 22, 24, 26, 27, 29, 30, 32, 34, 36, 37, 38, 40, 42, 48, 101, 102, 103] as $id) {
            $node = $root->appendChild($public->createElement('N'));
            $node->setAttribute('id', (string) $id);
            foreach (['F1', 'T1', 'F2', 'T2', 'A', 'S', 'Q', 'B'] as $attribute) {
                $node->setAttribute($attribute, $id === 4 && in_array($attribute, ['F1', 'T1'], true) ? $studentNumber : '');
            }
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $envelope = $document->appendChild($document->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Envelope'));
        $body = $envelope->appendChild($document->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Body'));
        $operation = $body->appendChild($document->createElementNS('urn:nowpardaz', 'golInfo'));
        $parameters = [
            'login' => $username,
            'pass' => $password,
            'sec' => 'AA0ECD9901',
            'pub' => $public->saveXML($root),
            'pri' => '<Root><N UQID="245" id="5" F="" T=""/><N UQID="10" id="10" F="" T=""/><N UQID="153" id="20" F="" T=""/><N UQID="17450" id="24" F="" T=""/></Root>',
            'mor' => '',
            'iFID' => '1132',
        ];
        foreach ($parameters as $name => $value) {
            $element = $operation->appendChild($document->createElementNS('urn:nowpardaz', $name));
            $element->appendChild($document->createTextNode($value));
        }

        try {
            $response = Http::connectTimeout(10)->timeout(40)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['SOAPAction' => '"urn:nowpardaz/golInfo"'])
                ->withBody($document->saveXML(), 'text/xml; charset=utf-8')
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
