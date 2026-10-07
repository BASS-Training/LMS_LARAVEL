<div class="space-y-1 text-left text-xs leading-5 text-slate-600">
    <label class="flex items-start gap-3">
        <input type="checkbox" name="refund_consent" value="1" required
               class="mt-1 rounded border-gray-300 text-bass-red focus:ring-bass-red">
        <span>{{ $refundSettings->consentText() }}</span>
    </label>
    <a href="{{ route('shop.refund-policy') }}" target="_blank" rel="noopener noreferrer" class="ml-7 inline-block font-semibold text-bass-red underline">Lihat Kebijakan Refund</a>
</div>
