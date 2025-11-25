<?php 
namespace OSW3\Api\Factory;

use Symfony\Component\Yaml\Yaml;
use OSW3\Api\Encoder\ToonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

final class ResponseEncoderFactory
{
    public function __construct(
        private readonly ToonEncoder $toonEncoder,
    ) {}


    public function encodeXmlResponse(string $data): ?string
    {
        $data = json_decode($data, true);
        $serializer = new Serializer([], [new XmlEncoder()]);

        return $serializer->encode($data, 'xml');
    }

    public function encodeYamlResponse(string $data): ?string 
    {
        $data = json_decode($data, true);
        return Yaml::dump($data, 2, 4, Yaml::DUMP_OBJECT_AS_MAP);
    }

    public function encodeCsvResponse(string $data): ?string
    {
        $data = json_decode($data, true);
        $serializer = new Serializer([], [new CsvEncoder()]);
        
        return $serializer->encode($data, 'csv');
    }

    public function encodeToonResponse(string $data): ?string
    {
        $data = json_decode($data, true);
        return $this->toonEncoder->encode($data, 'toon');
    }
}