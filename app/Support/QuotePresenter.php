<?php

namespace App\Support;

/** Turns a PricingService quote (or a stored reservation) into display rows. */
class QuotePresenter
{
    /** @return array<int, array{text:string, tag:string, amount:string, discount:bool}> */
    public static function rows(array $lines, string $mode, ?string $promoName = null, ?string $promoNote = null, int $discount = 0): array
    {
        $rows = [];
        foreach ($lines as $l) {
            $rows[] = $mode === 'paket'
                ? ['text' => "{$l['label']} · {$l['hours']} jam", 'tag' => '', 'amount' => rp($l['sum']), 'discount' => false]
                : ['text' => $l['hours'].' jam × '.($l['rate'] ? rp($l['rate']) : 'gratis'), 'tag' => $l['label'], 'amount' => $l['rate'] ? rp($l['sum']) : 'Gratis', 'discount' => false];
        }
        if ($promoName && $discount > 0) {
            $rows[] = ['text' => 'Promo '.$promoName.($promoNote ? " ({$promoNote})" : ''), 'tag' => '', 'amount' => '−'.rp($discount), 'discount' => true];
        }

        return $rows;
    }

    public static function forQuote(array $q, string $date, int $hour): array
    {
        $freeLc = $q['mode'] === 'sewa' && $q['lc'] && collect($q['lines'])->contains(fn ($l) => $l['rate'] === 0);

        return [
            'rows' => self::rows($q['lines'], $q['mode'], $q['promo_name'], $q['promo_note'], $q['discount']),
            'total' => rp($q['total']),
            'total_raw' => $q['total'],
            'hours' => $q['hours'],
            'when' => tgl($date).' · '.OpHours::range($hour, $q['hours']).'. Dibayar di tempat.',
            'lc_note' => $freeLc ? 'Ruangan gratis berlaku dengan LC dan deposit uang. '.self::depositText() : '',
        ];
    }

    public static function depositText(): string
    {
        $deposit = trim((string) Settings::get('deposit'));

        return $deposit !== ''
            ? "Deposit berupa uang: {$deposit}, dibayar di tempat."
            : 'Deposit berupa uang, dibayar di tempat. Besarnya diinformasikan admin saat konfirmasi.';
    }
}
