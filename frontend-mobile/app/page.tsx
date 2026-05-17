"use client";
import { useState } from 'react';
import { searchVault } from './lib/api';

export default function MobileVault() {
  const [query, setQuery] = useState('');
  const [result, setResult] = useState<any>(null);

  const handleSearch = async () => {
    // Simulating our BDU Student Identity
    const data = await searchVault(query, 'BDU-IS-001', 'Information Systems');
    setResult(data);
  };

  return (
    <div className="min-h-screen bg-gray-100 p-4 font-sans">
      <div className="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden p-6">
        <h1 className="text-2xl font-bold text-blue-900 mb-4">Academia Vault</h1>
        
        <input 
          type="text" 
          placeholder="Search (e.g. ተዛማጅነት)" 
          className="w-full p-3 border border-gray-300 rounded-lg mb-4 text-black"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
        />
        
        <button 
          onClick={handleSearch}
          className="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition"
        >
          Search Vault
        </button>

        {result && (
          <div className="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h2 className="font-bold text-blue-800">Result:</h2>
            <pre className="text-sm text-gray-700 whitespace-pre-wrap mt-2">
              {JSON.stringify(result, null, 2)}
            </pre>
          </div>
        )}
      </div>
    </div>
  );
}