<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaLog;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\Phone;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Opening hours, day/night boundary, deposit, WhatsApp gateway and templates (FR-ADM-17). */
class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['s' => Settings::all(), 'logs' => WaLog::latest('id')->paginate(20)]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'open' => ['required', 'integer', 'between:0,23'],
            'close' => ['required', 'integer', 'between:1,47'],
            'day_end' => ['required', 'integer'],
            'max_days' => ['required', 'integer', 'between:1,120'],
            'deposit' => ['nullable', 'string', 'max:60'],
            'wa_display' => ['required', 'string', 'max:20'],
            'wa_number' => ['required', 'string', 'max:20'],
            'wa_admin' => ['required', 'string', 'max:200'],
            'wa_gateway' => ['nullable', Rule::in(array_keys(config('dmk.wa_gateways')))],
            'wa_token' => ['nullable', 'string', 'max:200'],
            'tpl_admin' => ['required', 'string', 'max:2000'], 'tpl_guest' => ['required', 'string', 'max:2000'],
            'tpl_confirm' => ['required', 'string', 'max:2000'], 'tpl_reject' => ['required', 'string', 'max:2000'],
            'analytics_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/'], 'meta_pixel_id' => ['nullable', 'regex:/^\d{6,20}$/'],
        ], ['analytics_id.regex' => 'ID GA4 berawalan G-, contoh G-ABC123.', 'meta_pixel_id.regex' => 'ID Pixel berupa angka.']);

        $open = (int) $data['open'];
        $close = (int) $data['close'];
        $closeOp = $close <= $open ? $close + 24 : $close;
        $errors = [];
        // One operational day must not exceed 24 hours, otherwise days would overlap.
        if ($closeOp - $open > 24 || $closeOp === $open) {
            $errors['close'] = 'Jam tutup harus setelah jam buka dan paling lama 24 jam.';
        }
        if ($data['day_end'] <= $open || $data['day_end'] >= $closeOp) {
            $errors['day_end'] = 'Batas tarif siang harus di antara jam buka dan jam tutup.';
        }
        if (! Phone::valid($data['wa_number'])) {
            $errors['wa_number'] = 'Format nomor tidak valid, contoh 082220413723.';
        }
        $admins = Phone::list($data['wa_admin']);
        if (! $admins || count($admins) > 10 || collect($admins)->contains(fn ($n) => ! Phone::valid($n))) {
            $errors['wa_admin'] = 'Isi 1–10 nomor valid dipisah koma, contoh 082220413723, 081234567890.';
        }
        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $values = [...$data, 'close' => $closeOp, 'wa_admin' => implode(',', array_map(Phone::normalize(...), $admins)), 'wa_number' => Phone::toWa($data['wa_number']),
            'deposit' => trim((string) ($data['deposit'] ?? '')), 'wa_gateway' => $data['wa_gateway'] ?? '', 'wa_auto_status' => $request->boolean('wa_auto_status'),
            'analytics_id' => $data['analytics_id'] ?? '', 'meta_pixel_id' => $data['meta_pixel_id'] ?? ''];
        // Keep the stored token when the field is left empty.
        if (($data['wa_token'] ?? '') === '') {
            unset($values['wa_token']);
        }
        Settings::set($values);

        return redirect()->route('admin.pengaturan')->with('ok', 'Pengaturan disimpan.');
    }

    public function testWa(WhatsAppService $wa)
    {
        $log = $wa->queueTest();

        return back()->with('ok', match ($log->status) {
            'sent' => 'Pesan uji terkirim ke nomor admin.',
            'simulated' => 'Gateway belum dipilih: pesan uji hanya dicatat di log.',
            default => 'Pesan uji gagal dikirim. Lihat kolom respons di log.',
        });
    }
}
