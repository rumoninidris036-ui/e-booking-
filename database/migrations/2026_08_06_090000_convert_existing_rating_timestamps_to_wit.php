<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ratings')
            ->select(['id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($ratings): void {
                foreach ($ratings as $rating) {
                    DB::table('ratings')
                        ->where('id', $rating->id)
                        ->update([
                            'created_at' => $this->convertUtcToWit($rating->created_at),
                            'updated_at' => $this->convertUtcToWit($rating->updated_at),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('ratings')
            ->select(['id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($ratings): void {
                foreach ($ratings as $rating) {
                    DB::table('ratings')
                        ->where('id', $rating->id)
                        ->update([
                            'created_at' => $this->convertWitToUtc($rating->created_at),
                            'updated_at' => $this->convertWitToUtc($rating->updated_at),
                        ]);
                }
            });
    }

    private function convertUtcToWit(?string $timestamp): ?string
    {
        return $timestamp === null
            ? null
            : CarbonImmutable::parse($timestamp, 'UTC')->addHours(9)->format('Y-m-d H:i:s');
    }

    private function convertWitToUtc(?string $timestamp): ?string
    {
        return $timestamp === null
            ? null
            : CarbonImmutable::parse($timestamp, 'Asia/Jayapura')->subHours(9)->format('Y-m-d H:i:s');
    }
};
