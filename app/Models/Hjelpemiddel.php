<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $hjelpemiddel_kategori_id
 * @property string $name
 * @property string|null $url
 * @property array<int, array{key: string, value: string}>|null $custom_fields
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read HjelpemiddelKategori $kategori
 */
class Hjelpemiddel extends Model
{
    use HasFactory;

    protected $table = 'hjelpemidler';

    protected $fillable = [
        'hjelpemiddel_kategori_id',
        'name',
        'url',
        'custom_fields',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<HjelpemiddelKategori, $this>
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(HjelpemiddelKategori::class, 'hjelpemiddel_kategori_id');
    }
}
