'use client';

import useSWR from 'swr';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { API_BASE_URL, apiGet } from '../../../../lib/api';

const CUSTOMER_ID = 'nguyenvana@example.com';
const fetcher = (p: string) => apiGet(p);

function StatusStepper({ current }: { current: string }) {
  const statuses = ['PENDING', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'CLOSED'] as const;
  const isRejected = current === 'REJECTED';
  const active = statuses.indexOf(current as any);

  return (
    <div style={{ display: 'flex', gap: 10, margin: '16px 0', flexWrap: 'wrap' }}>
      {statuses.map((s, i) => {
        if (s === 'REJECTED' && !isRejected) return null;
        const style: React.CSSProperties = {
          padding: '8px 10px', border: '1px solid #ddd', borderRadius: 8,
          background: '#f3f4f6', color: '#6b7280', fontWeight: 600
        };
        if (!isRejected && i <= active) { style.background = '#22c55e'; style.color = '#fff'; }
        if (isRejected && (s === 'PENDING' || s === 'REJECTED')) { style.background = '#ef4444'; style.color = '#fff'; }
        return <div key={s} style={style}>{s}</div>;
      })}
    </div>
  );
}

export default function ClaimDetailPage() {
  const { id } = useParams<{ id: string }>();
  const key = id ? `/api/customer/claims/${id}?customer_id=${encodeURIComponent(CUSTOMER_ID)}` : null;

  const { data: raw, error } = useSWR(key, fetcher, { revalidateOnFocus: false });

  if (error) return <div style={{ padding: 20, color: 'crimson' }}>Lỗi: {error.message}</div>;
  if (!raw)   return <div style={{ padding: 20 }}>Đang tải…</div>;

  const claim = (raw as any).data ?? raw;

  return (
    <div style={{ padding: 20 }}>
      <p><Link href="/customer">← Dashboard</Link> | <Link href="/customer/claims">Danh sách</Link></p>
      <h1>Chi tiết yêu cầu</h1>
      <p><b>ID:</b> {claim.id}</p>

      <StatusStepper current={claim.status} />

      <p><b>Trạng thái:</b> {claim.status}</p>
      <p><b>VIN:</b> {claim.vin}</p>
      <p><b>Mô tả:</b> {claim.description || '(không có)'}</p>
      <p><b>Ngày tạo:</b> {claim.created_at ? new Date(claim.created_at).toLocaleString('vi-VN') : '-'}</p>

      <h3>Tệp đính kèm ({claim.attachments?.length || 0})</h3>
      {Array.isArray(claim.attachments) && claim.attachments.length ? (
        <ul>
          {claim.attachments.map((f: any, i: number) => {
            const href = f.path ? `${API_BASE_URL}/${f.path}`.replace(/([^:]\/)\/+/g, '$1') : undefined;
            return (
              <li key={f.id || `${f.filename}-${i}`}>
                {href
                  ? <a href={href} target="_blank" rel="noopener noreferrer">{f.filename || 'attachment'}</a>
                  : (f.filename || 'attachment')}
                {typeof f.size === 'number' ? ` (${Math.round(f.size / 1024)} KB)` : ''}
              </li>
            );
          })}
        </ul>
      ) : <p style={{ color: '#6b7280' }}>Không có file đính kèm.</p>}
    </div>
  );
}
