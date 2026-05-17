const API_BASE_URL = "https://verbose-dollop-v694jjpp6qp2x659-8000.app.github.dev/api/v1";

export const searchVault = async (query: string, studentId: string, dept: string) => {
    try {
        const response = await fetch(`${API_BASE_URL}/search?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Student-ID': studentId,
                'X-Department': dept,
            },
        });

        if (!response.ok) return { error: "Vault access denied" };
        return await response.json();
    } catch (error) {
        return { error: "Backend Connection Failed" };
    }
};