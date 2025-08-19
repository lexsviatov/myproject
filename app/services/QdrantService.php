<?php
namespace app\services;

class QdrantService
{
    private string $host;
    private string $apiKey;

    public function __construct(string $host = "http://localhost:6333", string $apiKey = "")
    {
        $this->host = rtrim($host, "/");
        $this->apiKey = $apiKey;
    }

    private function request(string $method, string $url, array $data = null): array
    {
        $ch = curl_init();
        $headers = ["Content-Type: application/json"];
        if (!empty($this->apiKey)) {
            $headers[] = "api-key: {$this->apiKey}";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->host . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception("Curl error: " . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    public function createCollection(string $name, int $dim = 1024): array
    {
        return $this->request("PUT", "/collections/{$name}", [
            "vectors" => [
                "size" => $dim,
                "distance" => "Cosine"
            ]
        ]);
    }

    public function addPoints(string $collection, array $points): array
    {
        return $this->request("PUT", "/collections/{$collection}/points", [
            "points" => $points
        ]);
    }

    public function search(string $collection, array $vector, int $limit = 5): array
    {
        return $this->request("POST", "/collections/{$collection}/points/search", [
            "vector" => $vector,
            "limit" => $limit
        ]);
    }
}
