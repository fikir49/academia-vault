const express = require('express');
const cors = require('cors');
const axios = require('axios');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 8000;
const STORAGE_SERVICE_URL = process.env.STORAGE_SERVICE_URL || 'http://localhost:5000';

app.use(cors());
app.use(express.json());

console.log("=================================================");
console.log(`📡 GATEWAY CORE INITIALIZING...`);
console.log(`🔗 Target Storage Cluster Node: ${STORAGE_SERVICE_URL}`);
console.log("=================================================");

// 1. Gateway Pass-Through Proxy for Vector Searching
app.post('/api/v1/search', async (req, res) => {
    try {
        const { query } = req.body;
        if (!query) {
            return res.status(400).json({ error: 'Search query parameter is required.' });
        }

        console.log(`🔀 Gateway routing request to Storage Cluster for: "${query}"`);

        // Forward payload to the dedicated vector processing machine
        const response = await axios.post(`${STORAGE_SERVICE_URL}/api/v1/storage/search`, {
            query: query
        });

        // Return perfectly formatted structural results to client
        res.json({
            gateway_status: 'resolved',
            ...response.data
        });

    } catch (error) {
        console.error("Gateway Search Proxy Error Event:", error.message);
        if (error.response) {
            return res.status(error.response.status).json({
                error: "Storage Service failed to evaluate query.",
                details: error.response.data
            });
        }
        res.status(500).json({ error: "Gateway internal transport breakdown.", message: error.message });
    }
});

// 2. Gateway Core System Health Check
app.get('/health', async (req, res) => {
    try {
        const storageHealth = await axios.get(`${STORAGE_SERVICE_URL}/health`);
        res.json({
            gateway: 'online',
            timestamp: new Date().toISOString(),
            dependencies: {
                storage_node: storageHealth.data
            }
        });
    } catch (error) {
        res.status(502).json({
            gateway: 'online',
            timestamp: new Date().toISOString(),
            dependencies: {
                storage_node: "OFFLINE"
            }
        });
    }
});

app.listen(PORT, () => {
    console.log(`=================================================`);
    console.log(`🚀 GATEWAY ENGINE ALIVE ON PORT: ${PORT}`);
    console.log(`=================================================`);
});
