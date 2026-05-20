const API_BASE_URL = "https://verbose-dollop-v694jjpp6qp2x659-8000.app.github.dev/api/v1";

// app/lib/api.ts

// app/lib/api.ts

export const searchVault = async (query: string, studentId: string, dept: string) => {
    // I am using your EXACT URL from the previous message
    const API_BASE_URL = "https://verbose-dollop-v694jjpp6qp2x659-8000.app.github.dev/api/v1";

    try {
        const response = await fetch(`${API_BASE_URL}/search?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'X-Department': dept,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            console.error("Server responded with error:", response.status);
            return { error: "Vault node unreachable", status: response.status };
        }

        return await response.json();
    } catch (error) {
        console.error("Fetch failed:", error);
        return { error: "Connection failed", message: "Network bridge blocked" };
    }
};