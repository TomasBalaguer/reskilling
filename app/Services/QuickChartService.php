<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuickChartService
{
    /**
     * Generate a radar chart using QuickChart API
     * 
     * @param array $competencias Array of competencies with 'nombre' and 'puntaje'
     * @return string Base64 encoded image or fallback URL
     */
    public static function generateRadarChart(array $competencias): string
    {
        try {
            // Prepare labels - split long names into multiple lines
            $labels = array_map(function($comp) {
                $nombre = $comp['nombre'];
                // Split long names for better display
                if (strlen($nombre) > 12) {
                    $words = explode(' ', $nombre);
                    if (count($words) >= 2) {
                        $mid = ceil(count($words) / 2);
                        return [
                            implode(' ', array_slice($words, 0, $mid)),
                            implode(' ', array_slice($words, $mid))
                        ];
                    }
                }
                return $nombre;
            }, $competencias);
            
            // Extract scores
            $data = array_column($competencias, 'puntaje');
            
            // Build chart configuration
            $chartConfig = [
                'type' => 'radar',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'data' => $data,
                            'fill' => true,
                            'backgroundColor' => 'rgba(139, 92, 246, 0.25)',
                            'borderColor' => 'rgb(139, 92, 246)',
                            'pointBackgroundColor' => 'rgb(139, 92, 246)',
                            'pointBorderColor' => '#fff',
                            'pointHoverBackgroundColor' => '#fff',
                            'pointHoverBorderColor' => 'rgb(139, 92, 246)',
                            'borderWidth' => 2,
                            'pointRadius' => 4,
                            'pointBorderWidth' => 2
                        ]
                    ]
                ],
                'options' => [
                    'responsive' => false,
                    'maintainAspectRatio' => false,
                    'legend' => ['display' => false],
                    'plugins' => [
                        'legend' => ['display' => false],
                        'title' => [
                            'display' => false
                        ]
                    ],
                    'scale' => [
                        'min' => 0,
                        'max' => 10,
                        'ticks' => [
                            'min' => 0,
                            'max' => 10,
                            'stepSize' => 2,
                            'beginAtZero' => true,
                            'font' => [
                                'size' => 12
                            ],
                            'color' => '#64748b',
                            'backdropColor' => 'transparent'
                        ],
                        'gridLines' => [
                            'color' => '#e2e8f0',
                            'lineWidth' => 1,
                            'circular' => true
                        ],
                        'angleLines' => [
                            'color' => '#cbd5e1',
                            'lineWidth' => 1,
                            'display' => true
                        ],
                        'pointLabels' => [
                            'font' => [
                                'size' => 11,
                                'weight' => '600'
                            ],
                            'color' => '#1e293b'
                        ]
                    ]
                ]
            ];
            
            // Make POST request to QuickChart
            $response = Http::timeout(10)->post('https://quickchart.io/chart', [
                'width' => 550,
                'height' => 550,
                'format' => 'png',
                'backgroundColor' => 'transparent',
                'chart' => $chartConfig
            ]);
            
            if ($response->successful()) {
                // Return base64 encoded image
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
            // Fallback to GET method with URL encoding
            $chartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
                'type' => 'radar',
                'data' => [
                    'labels' => array_map(function($comp) {
                        return substr($comp['nombre'], 0, 15);
                    }, $competencias),
                    'datasets' => [[
                        'data' => $data,
                        'fill' => true,
                        'backgroundColor' => 'rgba(139, 92, 246, 0.3)',
                        'borderColor' => 'rgb(139, 92, 246)',
                        'borderWidth' => 2
                    ]]
                ]
            ])) . '&width=400&height=400&backgroundColor=transparent';
            
            return $chartUrl;
            
        } catch (\Exception $e) {
            Log::error('QuickChart generation failed: ' . $e->getMessage());
            
            // Return empty image placeholder
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        }
    }
}