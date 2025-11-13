'use client';

import React, { useState } from 'react';
import useSWR from 'swr';
import { useRouter } from 'next/navigation';
import { api, API_BASE_URL } from '../../../../lib/api';

const CUSTOMER_ID = 'nguyenvana@example.com';

const fetcher = async (p: string) => {
  const r = await fetch(`${API_BASE_URL}${p}`, { cache: 'no-store' });
  if (!r.ok) throw new Error(await r.text());
  return r.json();
};

const MAX_FILES = 6;
const MAX_SIZE_MB = 10;

export default function NewClaimPage() {
  const router = useRouter();
  const [description, setDescription] = useState('');
  const [selectedVin, setSelectedVin] = useState('');
  const [files, setFiles] = useState<FileList | null>(null);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const { data: vehiclesRaw, error: vErr } = useSWR(
    `/api/customer/vehicles?ownerId=${encodeURIComponent(CUSTOMER_ID)}`,
    fetcher
  );

  const vehicles: any[] = Array.isArray(vehiclesRaw)
    ? vehiclesRaw
    : vehiclesRaw?.items ?? vehiclesRaw?.data ?? [];

  function validate(): string | null {
    if (!selectedVin) return 'Bạn phải chọn một chiếc xe.';
    if (files && files.length > MAX_FILES) return `Tối đa ${MAX_FILES} tệp.`;
    if (files) {
      for (const f of Array.from(files)) {
        if (f.size > MAX_SIZE_MB * 1024 * 1024) {
          return `Mỗi tệp tối đa ${MAX_SIZE_MB}MB.`;
        }
      }
    }
    if (description.trim().length < 10) {
      return 'Mô tả tối thiểu 10 ký tự.';
    }
    return null;
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    const v = validate();
    if (v) {
      setErr(v);
      return;
    }

    setBusy(true);
    setErr(null);

    try {
      const created = await api.createCustomerClaim({
        customer_id: CUSTOMER_ID as any, // sau này đổi sang ID số thì bỏ as any
        vin: selectedVin,
        description,
      });

      const id = (created as any).data?.id || (created as any).id;

      if (files && files.length > 0) {
        await api.uploadClaimAttachments(id, CUSTOMER_ID as any, files);
      }

      router.push(`/customer/claims/${id}`);
    } catch (e: any) {
      setErr(e?.message || 'Gửi thất bại');
      setBusy(false);
    }
  }

  if (vErr) {
    return (
      <div style={{ padding: 20, color: 'crimson' }}>
        Lỗi tải xe: {vErr.message}
      </div>
    );
  }

  if (!vehiclesRaw) {
    return <div style={{ padding: 20 }}>Đang tải xe…</div>;
  }

  const previews = files ? Array.from(files).slice(0, 4) : [];

  return (
    <form onSubmit={submit} style={{ padding: 20, maxWidth: 560 }}>
      <h2>Tạo yêu cầu bảo hành</h2>

      {/* Chọn xe */}
      <div style={{ margin: '12px 0' }}>
        <label>Chọn xe:</label>
        <br />
        <select
          value={selectedVin}
          onChange={(e) => setSelectedVin(e.target.value)}
          required
          style={{ width: 340, padding: 6 }}
        >
          <option value="">-- Chọn xe --</option>
          {vehicles.map((v) => (
            <option key={v.id ?? v.vin} value={v.vin}>
              {v.model ?? 'N/A'} — VIN: {v.vin}{' '}
              {v.year ? `(${v.year})` : ''}
            </option>
          ))}
        </select>
      </div>

      {/* Mô tả vấn đề */}
      <div style={{ margin: '12px 0' }}>
        <label>Mô tả vấn đề:</label>
        <br />
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          rows={4}
          style={{ width: 340, padding: 6 }}
          placeholder="Mô tả chi tiết lỗi/hiện tượng…"
          required
        />
        <small style={{ color: '#6b7280' }}>Tối thiểu 10 ký tự.</small>
      </div>

      {/* Đính kèm file */}
      <div style={{ margin: '12px 0' }}>
        <label>Đính kèm (ảnh/video):</label>
        <br />
        <input
          type="file"
          multiple
          onChange={(e) => setFiles(e.target.files)}
        />
        <div
          style={{
            display: 'flex',
            gap: 8,
            marginTop: 8,
            flexWrap: 'wrap',
          }}
        >
          {previews.map((f, i) => (
            <div
              key={i}
              style={{
                fontSize: 12,
                color: '#6b7280',
                border: '1px solid #eee',
                padding: 6,
                borderRadius: 6,
              }}
            >
              {f.name} ({Math.round(f.size / 1024)} KB)
            </div>
          ))}
        </div>
        <small style={{ color: '#6b7280' }}>
          Tối đa {MAX_FILES} tệp, mỗi tệp ≤ {MAX_SIZE_MB}MB.
        </small>
      </div>

      {err && <p style={{ color: 'crimson' }}>{err}</p>}

      <button type="submit" disabled={busy} style={{ padding: '8px 12px' }}>
        {busy ? 'Đang gửi…' : 'Gửi yêu cầu'}
      </button>
    </form>
  );
}
