"use client";

import React, { useState } from 'react';

const styles = {
    container: {
        padding: '40px',
        maxWidth: '600px',
        margin: '0 auto',
        color: '#fff',
    },
    form: {
        display: 'grid',
        gap: '15px',
    },
    label: {
        display: 'block',
        marginBottom: '5px',
    },
    input: {
        width: '100%',
        padding: '10px',
        color: '#000', 
        backgroundColor: '#fff',
        border: 'none',
        borderRadius: '5px',
        boxSizing: 'border-box' as 'border-box',
    },
    button: {
        padding: '12px',
        backgroundColor: '#0070f3', 
        color: 'white',
        border: 'none',
        borderRadius: '5px',
        cursor: 'pointer',
        fontSize: '16px',
    }
};

export default function RegisterVehiclePage() {
    const [vin, setVin] = useState('');
    const [model, setModel] = useState('');
    const [year, setYear] = useState('');
    const [color, setColor] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
    
        console.log("Dữ liệu form:", { vin, model, year, color });
        alert("Đã gửi (xem console)");
    };

    return (
        <div style={styles.container}>
            <h1>Đăng Ký Xe Mới (Ticket #21)</h1>
            
            <form onSubmit={handleSubmit} style={styles.form}>
                <div>
                    <label htmlFor="vin" style={styles.label}>Số VIN (17 ký tự)</label>
                    <input
                        id="vin"
                        type="text"
                        value={vin}
                        onChange={(e) => setVin(e.target.value)}
                        required
                        style={styles.input}
                        maxLength={17}
                    />
                </div>
                <div>
                    <label htmlFor="model" style={styles.label}>Model xe (Vd: VF8, VF9)</label>
                    <input
                        id="model"
                        type="text"
                        value={model}
                        onChange={(e) => setModel(e.target.value)}
                        required
                        style={styles.input}
                    />
                </div>
                <div>
                    <label htmlFor="year" style={styles.label}>Năm sản xuất</label>
                    <input
                        id="year"
                        type="number"
                        value={year}
                        onChange={(e) => setYear(e.target.value)}
                        required
                        style={styles.input}
                    />
                </div>
                <div>
                    <label htmlFor="color" style={styles.label}>Màu sắc</label>
                    <input
                        id="color"
                        type="text"
                        value={color}
                        onChange={(e) => setColor(e.target.value)}
                        style={styles.input}
                    />
                </div>
                <button type="submit" style={styles.button}>
                    Đăng Ký Xe
                </button>
            </form>
        </div>
    );
}