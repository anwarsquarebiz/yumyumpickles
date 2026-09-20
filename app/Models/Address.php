<?php

namespace App\Models;

use App\Enums\AddressType;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'first_name',
        'last_name',
        'company',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
        'country_code',
        'phone',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AddressType::class,
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Immutable copy stored on an order so later address-book edits never rewrite history.
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company' => $this->company,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
        ];
    }

    public function oneLine(): string
    {
        return collect([
            $this->fullName(),
            $this->address_line1,
            $this->city,
            $this->province,
            $this->postal_code,
            $this->country_code,
        ])->filter()->implode(', ');
    }
}
