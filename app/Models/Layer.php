<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Layer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'geometry',
    ];

    protected $casts = [
        'geometry' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($layer) {

            if (!$layer->geometry) {
                $layer->tryProcessRecentGeoJsonFile();
            }
        });

        static::updating(function ($layer) {

            if ($layer->geometry) {
                $layer->tryProcessRecentGeoJsonFile();
            }
        });
    }

    public function getGeometryTextAttribute()
    {
        if ($this->geometry) {
            return DB::selectOne("SELECT ST_AsText(?) as text", [$this->geometry])->text;
        }
        return null;
    }

    public function getGeometryGeojsonAttribute()
    {
        if ($this->geometry) {
            return DB::selectOne("SELECT ST_AsGeoJSON(?) as geojson", [$this->geometry])->geojson;
        }
        return null;
    }

    public function setGeometryAttribute($value)
    {
        if (($value === null || $value === '') && isset($this->attributes['geometry']) && $this->attributes['geometry']) {

            if ($value === null && func_num_args() > 0) {
                $this->attributes['geometry'] = null;
                return;
            }
            return;
        }

        if ($value && is_string($value)) {
            if (str_starts_with($value, '01') && ctype_xdigit($value)) {
                $this->attributes['geometry'] = $value;
                return;
            }

            $jsonData = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['type'])) {

                if ($jsonData['type'] === 'Feature' && isset($jsonData['geometry'])) {
                    $geometryJson = json_encode($jsonData['geometry']);
                    $this->attributes['geometry'] = DB::selectOne("SELECT ST_GeomFromGeoJSON(?) as geom", [$geometryJson])->geom;
                } elseif (in_array($jsonData['type'], ['Point', 'LineString', 'Polygon', 'MultiPoint', 'MultiLineString', 'MultiPolygon'])) {
                    $this->attributes['geometry'] = DB::selectOne("SELECT ST_GeomFromGeoJSON(?) as geom", [$value])->geom;
                } else {
                    throw new \InvalidArgumentException('Formato GeoJSON inválido');
                }
            } else {

                if (preg_match('/^(POINT|LINESTRING|POLYGON|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON)\s*\(/i', $value)) {
                    $this->attributes['geometry'] = DB::selectOne("SELECT ST_GeomFromText(?, 4326) as geom", [$value])->geom;
                } else {

                    $this->attributes['geometry'] = $value;
                }
            }
        } elseif ($value === null || $value === '') {

            if (!isset($this->attributes['geometry']) || !$this->attributes['geometry']) {
                $this->attributes['geometry'] = $value;
            }
        } else {
            $this->attributes['geometry'] = $value;
        }
    }

    public function scopeIntersects($query, $geometry)
    {
        return $query->whereRaw("ST_Intersects(geometry, ST_GeomFromText(?, 4326))", [$geometry]);
    }

    public function scopeWithin($query, $geometry)
    {
        return $query->whereRaw("ST_Within(geometry, ST_GeomFromText(?, 4326))", [$geometry]);
    }

    public function scopeNearby($query, $geometry, $distance = 1000)
    {
        return $query->whereRaw("ST_DWithin(geometry, ST_GeomFromText(?, 4326), ?)", [$geometry, $distance]);
    }

    public function tryProcessRecentGeoJsonFile()
    {
        try {

            $uploadDirs = [
                storage_path('app/private/geojson-uploads'),
                storage_path('app/private/livewire-tmp'),
                storage_path('app/geojson-uploads'),
            ];

            $recentFiles = [];
            $thirtyMinutesAgo = time() - 1800;

            foreach ($uploadDirs as $uploadDir) {
                if (!is_dir($uploadDir)) continue;

                $files = scandir($uploadDir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;

                    $filePath = $uploadDir . '/' . $file;
                    if (is_file($filePath) && filemtime($filePath) > $thirtyMinutesAgo) {

                        if (str_ends_with(strtolower($file), '.json') || str_ends_with(strtolower($file), '.geojson') || str_contains($file, '.json') || str_contains($file, '.geojson')) {
                            $recentFiles[] = $filePath;
                        }
                    }
                }
            }


            if (!empty($recentFiles)) {

                usort($recentFiles, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });

                $latestFile = $recentFiles[0];
                $geoJsonContent = file_get_contents($latestFile);

                if ($geoJsonContent) {
                    $geoJsonData = json_decode($geoJsonContent, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $geometry = null;


                        if (isset($geoJsonData['type']) && $geoJsonData['type'] === 'Feature' && isset($geoJsonData['geometry'])) {
                            $geometry = json_encode($geoJsonData['geometry']);
                        } elseif (isset($geoJsonData['type']) && in_array($geoJsonData['type'], ['Point', 'LineString', 'Polygon', 'MultiPoint', 'MultiLineString', 'MultiPolygon'])) {
                            $geometry = json_encode($geoJsonData);
                        }

                        if ($geometry) {
                            $this->geometry = $geometry;
                            Log::info('Geometria processada com sucesso: ' . $geometry);
                        }
                    }
                }
            }
        } catch (\Exception $e) {

            Log::error('Falha ao processar GeoJSON: ' . $e->getMessage());
        }
    }
}
