<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $hjelpemiddel_kategori_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $url
 * @property array<int, array{key: string, value: string}>|null $custom_fields
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read HjelpemiddelKategori $kategori
 * @property-read Hjelpemiddel|null $parent
 * @property-read Collection<int, Hjelpemiddel> $children
 */
class Hjelpemiddel extends Model
{
    use HasFactory;

    protected $table = 'hjelpemidler';

    protected $fillable = [
        'hjelpemiddel_kategori_id',
        'parent_id',
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

    /**
     * @return BelongsTo<Hjelpemiddel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Hjelpemiddel::class, 'parent_id');
    }

    /**
     * @return HasMany<Hjelpemiddel, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Hjelpemiddel::class, 'parent_id')->orderBy('sort_order');
    }
}
