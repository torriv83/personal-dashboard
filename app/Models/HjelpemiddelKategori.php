<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Hjelpemiddel> $hjelpemidler
 */
class HjelpemiddelKategori extends Model
{
    use HasFactory;

    protected $table = 'hjelpemiddel_kategorier';

    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Hjelpemiddel, $this>
     */
    public function hjelpemidler(): HasMany
    {
        return $this->hasMany(Hjelpemiddel::class, 'hjelpemiddel_kategori_id');
    }
}
