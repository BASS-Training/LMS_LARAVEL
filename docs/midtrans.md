Berikut adalah detail API untuk melakukan *Cancel* dan *Refund* pada Midtrans, beserta perbedaan kapan harus menggunakannya.

## 1. Cancel API

Gunakan **Cancel API** jika status transaksi masih `pending`, `authorize`, atau `capture` (belum *settlement*).

* **HTTP Method:** `POST`
* **Endpoint:**
* **Sandbox:** `[https://api.sandbox.midtrans.com/v2/](https://api.sandbox.midtrans.com/v2/){order_id_atau_transaction_id}/cancel`
* **Production:** `[https://api.midtrans.com/v2/](https://api.midtrans.com/v2/){order_id_atau_transaction_id}/cancel`


* **Headers:**
* `Accept: application/json`
* `Content-Type: application/json`
* `Authorization: Basic {Server-Key-yang-di-encode-base64}`



## 2. Refund API

Gunakan **Refund API** jika status transaksi sudah `settlement` (dana sudah masuk). API ini akan membalikkan dana ke pelanggan untuk metode pembayaran yang mendukung (seperti Kartu Kredit, GoPay, ShopeePay, QRIS, dan Akulaku).

* **HTTP Method:** `POST`
* **Endpoint:**
* **Sandbox:** `[https://api.sandbox.midtrans.com/v2/](https://api.sandbox.midtrans.com/v2/){order_id_atau_transaction_id}/refund`
* **Production:** `[https://api.midtrans.com/v2/](https://api.midtrans.com/v2/){order_id_atau_transaction_id}/refund`


* **Headers:**
* `Accept: application/json`
* `Content-Type: application/json`
* `Authorization: Basic {Server-Key-yang-di-encode-base64}`


* **Body (JSON):**
```json
{
  "refund_key": "referensi_refund_dari_merchant",
  "amount": 50000,
  "reason": "Alasan pengembalian dana"
}

```


*Catatan: `refund_key` bersifat opsional namun disarankan agar tidak terjadi *double refund*. Jika dikosongkan, Midtrans akan membuatkannya otomatis.*

> **Penting:** Fitur Refund via API mungkin belum aktif secara bawaan untuk akun Anda. Jika Anda mendapatkan *error* saat mencoba Refund API, Anda harus mengajukan permohonan pengaktifan fitur Refund ke tim Support Midtrans terlebih dahulu.

### Kebijakan Aplikasi

* Refund selalu menggunakan nominal penuh, termasuk biaya layanan.
* Kartu kredit, GoPay, ShopeePay, QRIS, dan metode lain yang didukung diproses melalui Refund API.
* Transfer bank/Virtual Account tidak didukung Refund API dan diproses manual oleh admin.
* Refund otomatis baru dinyatakan selesai setelah notifikasi Midtrans memuat `bank_confirmed_at`.
* Akses kursus baru dicabut setelah refund otomatis dikonfirmasi atau admin mengonfirmasi refund manual selesai.
