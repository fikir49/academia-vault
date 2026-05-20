'use client'

import { useState } from 'react';
import { searchVault } from './lib/api';

export default function VictoryPage() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);

    const handleSearch = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        try {
            const data = await searchVault(query);
            setResults(data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div style={{ padding: '40px', fontFamily: 'sans-serif', backgroundColor: '#0f172a', color: '#f8fafc', minHeight: '100vh' }}>
            <h1 style={{ color: '#38bdf8' }}>🎓 Academia Vault — Core Engine Verified</h1>
            <p style={{ color: '#94a3b8' }}>Wired directly to your local database container layer.</p>
            
            <form onSubmit={handleSearch} style={{ margin: '20px 0', display: 'flex', gap: '10px' }}>
                <input 
                    type="text" 
                    value={query} 
                    onChange={(e) => setQuery(e.target.value)} 
                    placeholder="Type relevance, chapter, or dynamic..." 
                    style={{ padding: '12px', borderRadius: '6px', border: '1px solid #334155', backgroundColor: '#1e293b', color: '#fff', width: '300px' }}
                />
                <button type="submit" style={{ padding: '12px 24px', borderRadius: '6px', border: 'none', backgroundColor: '#38bdf8', color: '#0f172a', fontWeight: 'bold', cursor: 'pointer' }}>
                    {loading ? 'Searching...' : 'Execute Search'}
                </button>
            </form>

            <div style={{ marginTop: '30px' }}>
                {results.length === 0 ? (
                    <p style={{ color: '#64748b' }}>No rows rendered yet. Type a term and strike the button.</p>
                ) : (
                    <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: '10px', backgroundColor: '#1e293b', borderRadius: '8px', overflow: 'hidden' }}>
                        <thead>
                            <tr style={{ backgroundColor: '#334155', textAlign: 'left' }}>
                                <th style={{ padding: '12px' }}>Source Document</th>
                                <th style={{ padding: '12px' }}>Extracted Technical Definition</th>
                                <th style={{ padding: '12px' }}>Weight (tf)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {results.map((item, index) => (
                                <tr key={index} style={{ borderBottom: '1px solid #334155' }}>
                                    <td style={{ padding: '12px', color: '#38bdf8', fontWeight: 'bold' }}>{item.source_pdf}</td>
                                    <td style={{ padding: '12px' }}>{item.definition}</td>
                                    <td style={{ padding: '12px', color: '#4ade80' }}>{item.relevance_score}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
}
