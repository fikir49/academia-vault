"use client";
import { useState } from 'react';
import { searchVault } from './lib/api';

export default function MobileVault() {
  const [query, setQuery] = useState('');
  const [result, setResult] = useState<any>(null);
  const [loading, setLoading] = useState(false);

  const handleSearch = async () => {
    if (!query.trim()) return;
    setLoading(true);
    setResult(null); // Reset layout state
    
    try {
        const data = await searchVault(query, 'BDU-IS-001', 'Information Systems');
        console.log("Acquired Data:", data);
        
        // Ensure state is updated correctly depending on payload wrapping
        if (data && data.vault_results) {
            setResult(data);
        } else {
            setResult({ error: true, message: "No explicit search payloads returned from server." });
        }
    } catch (err) {
        setResult({ error: true, message: "Critical unhandled component error." });
    } finally {
        setLoading(false);
    }
};
  return (
    <div className="min-h-screen bg-slate-50 p-4 font-sans max-w-md mx-auto border-x border-slate-200">
      <header className="py-6 text-center">
        <h1 className="text-2xl font-black text-blue-900 tracking-tighter">ACADEMIA VAULT</h1>
        <div className="h-1 w-12 bg-blue-600 mx-auto mt-1 rounded-full"></div>
      </header>

      <div className="bg-white rounded-2xl shadow-sm p-5 mb-6 border border-slate-200">
        <input 
          type="text" 
          placeholder="Search Vault..." 
          className="w-full p-4 bg-slate-50 rounded-xl mb-3 text-black outline-none focus:ring-2 focus:ring-blue-500 transition-all"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
        />
        <button 
          onClick={handleSearch}
          disabled={loading}
          className="w-full bg-blue-600 text-white font-bold py-4 rounded-xl active:scale-95 transition-transform disabled:bg-slate-300"
        >
          {loading ? 'Accessing Node...' : 'Search'}
        </button>
      </div>

      {result && (
        <div className="space-y-4">
         {/* Only show the Dictionary box if it exists AND is not the 'No match' string */}
{result.dictionary && result.dictionary !== "No lexical match." && (
  <div className="bg-amber-50 border border-amber-100 p-4 rounded-2xl mb-4">
    <p className="text-[10px] font-black text-amber-600 uppercase mb-1">Oxford Lexicon</p>
    <p className="text-slate-800 text-sm leading-snug">{result.dictionary}</p>
  </div>
)}
{/* Replace your existing Error State at the bottom of page.tsx */}
{result?.error && (
  <div className="bg-red-50 border-2 border-red-200 p-6 rounded-2xl">
    <h3 className="text-red-800 font-black text-sm uppercase mb-2">System Error Detected</h3>
    <p className="text-red-700 text-xs font-mono bg-white p-3 rounded border border-red-100 mb-2">
       {result.message || result.error}
    </p>
    {result.line && <p className="text-[10px] text-red-400">Error at line: {result.line}</p>}
    <button 
      onClick={() => window.location.reload()}
      className="mt-4 text-xs font-bold text-red-600 underline"
    >
      Restart Node Connection
    </button>
  </div>
)}

{/* Technical Results */}
{result.vault_results && result.vault_results.length > 0 ? (
  result.vault_results.map((item: any, index: number) => (
    <div key={index} className="p-4 bg-white border border-slate-200 rounded-2xl mb-3">
       <p className="text-[10px] text-blue-600 font-bold mb-1">{item.source_pdf}</p>
       <p className="text-slate-700 text-sm italic">"{item.definition}"</p>
    </div>
  ))
) : (
  /* This will tell us if the backend actually sent back 0 results */
  <div className="text-center p-10 bg-slate-100 rounded-2xl border-2 border-dashed border-slate-200">
    <p className="text-slate-400 text-sm">No technical data found for "{query}"</p>
  </div>
)}

          {result.vault_results?.length === 0 && (
            <p className="text-center text-slate-400 text-sm py-10">No matches found in this decentralized node.</p>
          )}
        </div>
      )}
    </div>
    
  );
}