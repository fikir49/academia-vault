'use client'

import { useState } from 'react';
import { searchVault } from './lib/api';

export default function VictoryPage() {
    const [query, setQuery] = useState('');
    const [searchPayload, setSearchPayload] = useState<any>(null);
    const [loading, setLoading] = useState(false);

    const handleSearch = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        try {
            const data = await searchVault(query);
            setSearchPayload(data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const pipeline = searchPayload?.query_pipeline;
    const ftsMatches = searchPayload?.results?.fts_matches || [];

    return (
        <div style={{ padding: '40px', fontFamily: 'sans-serif', backgroundColor: '#0f172a', color: '#f8fafc', minHeight: '100vh' }}>
            <h1 style={{ color: '#38bdf8' }}>🎓 Academia Vault — Bilingual IR Engine</h1>
            <p style={{ color: '#94a3b8' }}>Cross-lingual Amharic-English search active.</p>
            
            <form onSubmit={handleSearch} style={{ margin: '20px 0', display: 'flex', gap: '10px' }}>
                <input 
                    type="text" 
                    value={query} 
                    onChange={(e) => setQuery(e.target.value)} 
                    placeholder="Type relevance, database, or ስለተዛማጅነቶች..." 
                    style={{ padding: '12px', borderRadius: '6px', border: '1px solid #334155', backgroundColor: '#1e293b', color: '#fff', width: '380px' }}
                />
                <button type="submit" style={{ padding: '12px 24px', borderRadius: '6px', border: 'none', backgroundColor: '#38bdf8', color: '#0f172a', fontWeight: 'bold', cursor: 'pointer' }}>
                    {loading ? 'Searching...' : 'Execute Search'}
                </button>
            </form>

            {/* NLP Pipeline Trace Box */}
            {pipeline && (
                <div style={{ marginTop: '20px', padding: '16px', backgroundColor: '#1e293b', border: '1px solid #334155', borderRadius: '8px' }}>
                    <h3 style={{ color: '#38bdf8', margin: '0 0 8px 0' }}>🔍 Cross-Lingual NLP Pipeline</h3>
                    <p style={{ margin: '4px 0' }}><strong>Raw Query:</strong> {pipeline.raw_query}</p>
                    <p style={{ margin: '4px 0' }}><strong>Normalized:</strong> {pipeline.normalized_query}</p>
                    <p style={{ margin: '4px 0' }}><strong>Stemmed Root:</strong> {pipeline.terms?.[0]?.stemmed || 'N/A'}</p>
                    <p style={{ margin: '4px 0' }}><strong>Translated Term:</strong> <span style={{ color: '#4ade80' }}>{pipeline.terms?.[0]?.english_translation || 'N/A'}</span></p>
                </div>
            )}

            {/* Results Display */}
            <div style={{ marginTop: '30px' }}>
                {!searchPayload ? (
                    <p style={{ color: '#64748b' }}>Type a query and click Execute Search.</p>
                ) : ftsMatches.length === 0 ? (
                    <p style={{ color: '#f87171' }}>No matching documents found in the vault.</p>
                ) : (
                    <div>
                        <h3 style={{ color: '#cbd5e1' }}>Retrieved Document Definitions</h3>
                        <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: '10px', backgroundColor: '#1e293b', borderRadius: '8px', overflow: 'hidden' }}>
                            <thead>
                                <tr style={{ backgroundColor: '#334155', textAlign: 'left' }}>
                                    <th style={{ padding: '12px' }}>Source Document</th>
                                    <th style={{ padding: '12px' }}>Matched Term</th>
                                    <th style={{ padding: '12px' }}>Extracted Technical Definition</th>
                                </tr>
                            </thead>
                            <tbody>
                                {ftsMatches.map((item: any, index: number) => (
                                    <tr key={index} style={{ borderBottom: '1px solid #334155' }}>
                                        <td style={{ padding: '12px', color: '#38bdf8', fontWeight: 'bold' }}>{item.doc_id}</td>
                                        <td style={{ padding: '12px', color: '#facc15' }}>{item.term}</td>
                                        <td style={{ padding: '12px' }}>{item.technical_context}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}