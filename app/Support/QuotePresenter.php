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
                : ($l['rate']
                    ? ['text' => $l['hours'].' jam × '.rp($l['rate']), 'tag' => $l['label'], 'amount' => rp($l['sum']), 'discount' => false]
                    // Rate 0 only happens for daytime rental with LC: the room is covered by the deposit.
                    : ['text' => $l['hours'].' jam dengan deposit', 'tag' => $l['label'], 'amount' => trim('Deposit '.self::deposit()), 'discount' => false]);
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
            'lc_note' => $freeLc ? self::depositText().' Deposit tidak termasuk total.' : '',
        ];
    }

    public static function deposit(): string
    {
        return trim((string) Settings::get('deposit'));
    }

    public static function depositText(): string
    {
        $deposit = self::deposit();

        return $deposit !== ''
            ? "Deposit {$deposit} berupa uang, dibayar di tempat."
            : 'Deposit berupa uang, dibayar di tempat. Besarnya diinformasikan admin saat konfirmasi.';
    }
}
