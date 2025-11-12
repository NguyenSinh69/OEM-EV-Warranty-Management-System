'use client';

import useSWR, { mutate } from 'swr';
import Link from 'next/link';
import { apiGet } from '../../../lib/api';

const CUSTOMER_ID = 'nguyenvana@example.com';
const fetcher = (p: string) => apiGet(p);

function Skeleton({ rows = 3 }: { rows?: number }) {
  return (
    <ul>
      {Array.from({ length: rows }).map((_, i) => (
        <li key={i} style={{ background: '#f3f4f6', height: 14, margin: '8px 0', borderRadius: 4 }} />
      ))}
    </ul>
  );
}

export default function CustomerDashboardPage() {
  const vehiclesKey = `/api/customer/vehicles?ownerId=${encodeURIComponent(CUSTOMER_ID)}`;
  const claimsKey   = `/api/customer/claims?customer_id=${encodeURIComponent(CUSTOMER_ID)}&limit=5`;

  const { data: vRaw, error: vErr, isLoading: vLoad } = useSWR(vehiclesKey, fetcher, { revalidateOnFocus: false });
  const { data: cRaw, error: cErr, isLoading: cLoad } = useSWR(claimsKey, fetcher, { revalidateOnFocus: false });

  const vehicles: any[] = Array.isArray(vRaw) ? vRaw : vRaw?.items ?? vRaw?.data ?? [];
  const claims:   any[] = Array.isArray(cRaw) ? cRaw : cRaw?.items ?? cRaw?.data ?? [];

  return (
    <div style={{ padding: 20, fontFamily: 'Inter, Arial, sans-serif' }}>
      <h1>Dashboard của tôi</h1>

      <div style={{ margin: '12px 0', display: 'flex', gap: 10 }}>
        <Link href="/customer/claims/new" style={{ padding: '8px 12px', background: '#2563eb', color: '#fff', borderRadius: 8 }}>
          + Tạo yêu cầu
        </Link>
        <Link href="/customer/claims" style={{ padding: '8px 12px', background: '#6b7280', color: '#fff', borderRadius: 8 }}>
          Xem lịch sử
        </Link>
      </div>

      <section style={{ border: '1px solid #e5e7eb', borderRadius: 10, padding: 16, marginTop: 12 }}>
        <h2>Xe của tôi {vehicles.length ? `(${vehicles.length})` : ''}</h2>
        {vLoad ? (
          <Skeleton rows={3} />
        ) : vErr ? (
          <div>
            <p style={{ color: 'crimson' }}>Lỗi tải xe: {vErr.message}</p>
            <button onClick={() => mutate(vehiclesKey)} style={{ padding: '6px 10px' }}>Thử lại</button>
          </div>
        ) : vehicles.length ? (
          <ul>
            {vehicles.map((x) => (
              <li key={x.id ?? x.vin}>
                <b>{x.model ?? 'N/A'}</b> — VIN: {x.vin} {x.year ? `(${x.year})` : ''}
              </li>
            ))}
          </ul>
        ) : (
          <p style={{ color: '#6b7280' }}>Chưa có xe nào.</p>
        )}
      </section>

      <section style={{ border: '1px solid #e5e7eb', borderRadius: 10, padding: 16, marginTop: 12 }}>
        <h2>Yêu cầu gần đây</h2>
        {cLoad ? (
          <Skeleton rows={5} />
        ) : cErr ? (
          <div>
            <p style={{ color: 'crimson' }}>Lỗi tải yêu cầu: {cErr.message}</p>
            <button onClick={() => mutate(claimsKey)} style={{ padding: '6px 10px' }}>Thử lại</button>
          </div>
        ) : claims.length ? (
          <ul>
            {claims.map((c) => (
              <li key={c.id}>
                <Link href={`/customer/claims/${c.id}`}>
                  {c.description || '(không mô tả)'} — <b>{c.status}</b>
                </Link>
                <span style={{ color: '#6b7280' }}> — {c.created_at ?? ''}</span>
              </li>
            ))}
          </ul>
        ) : (
          <p style={{ color: '#6b7280' }}>Không có yêu cầu gần đây.</p>
        )}
      </section>
    </div>
  );
}
