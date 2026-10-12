import { NextRequest, NextResponse } from 'next/server';

export async function GET(request: NextRequest) {
  const { searchParams } = new URL(request.url);
  const query = searchParams.get('query');

  if (!query) {
    return NextResponse.json({ success: true, results: { bilingual_matches: [], lexical_matches: [], fts_matches: [] } });
  }

  const userDept = request.headers.get('x-department') || 'Information Systems';
  const userClearance = request.headers.get('x-clearance-level') || '2';

  try {
    // Forward as POST to Laravel's cross-lingual Amharic-English pipeline
    const response = await fetch('http://127.0.0.1:8000/api/search/cross-lingual', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Department': userDept,
        'X-Clearance-Level': userClearance,
      },
      body: JSON.stringify({ query }),
    });

    if (!response.ok) {
      throw new Error(`Laravel backend returned status ${response.status}`);
    }

    const data = await response.json();
    return NextResponse.json(data);
  } catch (error) {
    console.error('Cross-lingual proxy error:', error);
    return NextResponse.json(
      { success: false, message: 'Failed to connect to cross-lingual backend IR service.' },
      { status: 500 }
    );
  }
}