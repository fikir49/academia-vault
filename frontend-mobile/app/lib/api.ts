export async function searchVault(query: string, dept: string = 'Information Systems', clearance: number = 2) {
  try {
    // Call Next.js local API proxy route (server-to-server handles Laravel communication)
    const response = await fetch(`/api/search?query=${encodeURIComponent(query)}`, {
      method: 'GET',
      headers: {
        'X-Department': dept,
        'X-Clearance-Level': clearance.toString(),
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Frontend retrieval failure:', error);
    return { success: false, results: { bilingual_matches: [], lexical_matches: [], fts_matches: [] } };
  }
}