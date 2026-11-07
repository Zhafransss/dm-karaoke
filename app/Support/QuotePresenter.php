<?php

namespace App\Support;

use App\Models\RoomType;

/** Turns a PricingService quote (or a stored reservation) into display rows. */
class QuotePresenter
{
    /** @return array<int, array{text:string, tag:string, amount:string, discount:bool}> */
    public static function rows(array $lines, string $mode, ?string $promoName = null, ?string $promoNote = null, int $discount = 0, ?RoomType $type = null): array
    {
        $rows = [];
        foreach ($lines as $l) {
            $rows[] = $mode === 'paket'
                ? ['text' => "{$l['label']} · {$l['hours']} jam", 'tag' => '', 'amount' => rp($l['sum']), 'discount' => false]
                : ($l['rate']
                    ? ['text' => $l['hours'].' jam × '.rp($l['rate']), 'tag' => $l['label'], 'amount' => rp($l['sum']), 'discount' => false]
                    // Rate 0 only happens for daytime rental with LC: the room is covered by the deposit.
                    : ['text' => $l['hours'].' jam dengan deposit', 'tag' => $l['label'], 'amount' => trim('Deposit '.($type?->depositLabel() ?? '')), 'discount' => false]);
        }
        if ($promoName && $discount > 0) {
            // "Promo Ulang Tahun" stays as is; "Sewa 3 Jam Bayar 2 Jam" becomes "Promo Sewa 3 Jam Bayar 2 Jam".
            $label = preg_match('/^promo\b/iu', $promoName) ? $promoName : 'Promo '.$promoName;
            $rows[] = ['text' => $label.($promoNote ? " ({$promoNote})" : ''), 'tag' => '', 'amount' => '−'.rp($discount), 'discount' => true];
        }

        return $rows;
    }

    public static function forQuote(array $q, string $date, int $hour, ?RoomType $type = null): array
    {
        $freeLc = $q['mode'] === 'sewa' && $q['lc'] && collect($q['lines'])->contains(fn ($l) => $l['rate'] === 0);

        return [
            'rows' => self::rows($q['lines'], $q['mode'], $q['promo_name'], $q['promo_note'], $q['discount'], $type),
            'total' => rp($q['total']),
            'total_raw' => $q['total'],
            'hours' => $q['hours'],
            'when' => tgl($date).' · '.OpHours::range($hour, $q['hours']).'. '.($type?->paymentNote() ?? 'Dibayar di tempat.'),
            'lc_note' => $freeLc && $type && ! $type->prepaid ? $type->depositText().' Deposit tidak termasuk total.' : '',
            'pay_note' => $type?->prepaid ? "{$type->name} wajib lunas di awal (tanpa deposit). Jadwalmu dikonfirmasi admin setelah pembayaran lunas." : '',
            // The applied promo (one per reservation), marked on the promo cards.
            'promo_id' => $q['promo_id'],
        ];
    }
}
