import { NextResponse } from 'next/server';

export async function GET(request: Request) {
    try {
        const { searchParams } = new URL(request.url);
        const query = searchParams.get('query') || '';
        const dept = request.headers.get('X-Department') || 'Information Systems';

        // Speak directly to Laravel over the internal loopback container network
        const response = await fetch(`http://127.0.0.1:8000/api/v1/search?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'X-Department': dept,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            return NextResponse.json({ vault_results: [] }, { status: response.status });
        }

        const data = await response.json();
        return NextResponse.json(data);
    } catch (error) {
        console.error("Proxy failure:", error);
        return NextResponse.json({ vault_results: [] }, { status: 500 });
    }
}
