<?php
namespace OSW3\Api\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;

final class XmlResponse extends Response
{
    public function __construct(string $xml = '', int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/xml; charset=UTF-8';
        parent::__construct($xml, $status, $headers);
    }

    /**
     * Crée une réponse XML à partir d’un tableau PHP.
     */
    public static function fromArray(array $data, string $rootNode = 'response', int $status = 200, array $headers = []): self
    {
        $xml = new \SimpleXMLElement("<{$rootNode}/>");
        self::arrayToXml($data, $xml);
        return new self($xml->asXML(), $status, $headers);
    }

    private static function arrayToXml(array $data, \SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild(is_numeric($key) ? "item{$key}" : $key);
                self::arrayToXml($value, $child);
            } else {
                $xml->addChild(is_numeric($key) ? "item{$key}" : $key, htmlspecialchars((string) $value));
            }
        }
    }
}
