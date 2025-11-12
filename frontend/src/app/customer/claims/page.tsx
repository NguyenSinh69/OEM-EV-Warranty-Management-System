'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { api } from '../../../lib/api';

const CUSTOMER_ID = 'nguyenvana@example.com';

export default function ClaimsListPage() {
  const [items, setItems] = useState<any[]>([]);
  const [status, setStatus] = useState<string>('');
  const [page, setPage] = useState<number>(1);
  const [limit] = useState<number>(10);
  const [loading, setLoading] = useState<boolean>(true);
  const [err, setErr] = useState<string | null>(null);

  async function load() {
    try {
      setLoading(true);
      setErr(null);
      const res = await api.listCustomerClaims({
        customer_id: CUSTOMER_ID,
        status: status || undefined,
        page,
        limit,
      });
      const data: any[] = Array.isArray(res) ? res : (res as any).items ?? (res as any).data ?? [];
      setItems(data);
    } catch (e: any) {
      setErr(e?.message || 'Tải danh sách thất bại');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [status, page, limit]);

  return (
    <div style={{ padding: 20 }}>
      <h1>Yêu cầu bảo hành của tôi</h1>

      <div style={{ display: 'flex', gap: 12, alignItems: 'center', margin: '12px 0' }}>
        <label>
          Trạng thái:{' '}
          <select value={status} onChange={(e) => { setPage(1); setStatus(e.target.value); }}>
            <option value="">(tất cả)</option>
            <option value="PENDING">PENDING</option>
            <option value="IN_PROGRESS">IN_PROGRESS</option>
            <option value="APPROVED">APPROVED</option>
            <option value="REJECTED">REJECTED</option>
            <option value="CLOSED">CLOSED</option>
          </select>
        </label>

        <Link href="/customer/claims/new" style={{ padding: '8px 12px', background: '#2563eb', color: '#fff', borderRadius: 8 }}>
          + Tạo yêu cầu
        </Link>

        {err && <span style={{ color: 'crimson' }}>Lỗi: {err}</span>}
      </div>

      {loading ? (
        <p>Đang tải…</p>
      ) : items.length === 0 ? (
        <p style={{ color: '#6b7280' }}>Không có yêu cầu nào.</p>
      ) : (
        <table border={1} cellPadding={6} style={{ width: '100%', maxWidth: 980 }}>
          <thead>
            <tr>
              <th>ID</th>
              <th>VIN</th>
              <th>Trạng thái</th>
              <th>Ngày tạo</th>
              <th>Mô tả</th>
            </tr>
          </thead>
          <tbody>
            {items.map((c) => (
              <tr key={c.id}>
                <td><Link href={`/customer/claims/${c.id}`}>{c.id}</Link></td>
                <td>{c.vin}</td>
                <td>{c.status}</td>
                <td>{c.created_at || '-'}</td>
                <td>{c.description || ''}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      <div style={{ marginTop: 12, display: 'flex', gap: 8 }}>
        <button disabled={page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>← Trang trước</button>
        <span>Trang {page}</span>
        <button disabled={items.length < limit} onClick={() => setPage((p) => p + 1)}>Trang sau →</button>
      </div>
    </div>
  );
}
