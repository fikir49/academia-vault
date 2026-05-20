export const searchVault = async (query: string, dept: string = 'Information Systems') => {
    try {
        // Calls our standard local API endpoint—no cryptographic tokens needed!
        const response = await fetch(`/api/search?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'X-Department': dept,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        return data.vault_results || [];
    } catch (error) {
        console.error("Frontend retrieval failure:", error);
        return [];
    }
};
