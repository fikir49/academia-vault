const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');
const { pipeline } = require('@xenova/transformers');

const app = express();
const PORT = process.env.PORT || 5000;

app.use(cors());
app.use(express.json());

let extractor;
(async () => {
    console.log("📥 Loading local vector extraction embedding pipeline...");
    extractor = await pipeline('feature-extraction', 'Xenova/all-MiniLM-L6-v2');
    console.log("✅ Vector AI Engine is fully active and loaded.");
})();

function cosineSimilarity(vecA, vecB) {
    let dotProduct = 0.0;
    for (let i = 0; i < vecA.length; i++) {
        dotProduct += vecA[i] * vecB[i];
    }
    return dotProduct;
}

app.post('/api/v1/storage/search', async (req, res) => {
    try {
        const { query } = req.body;
        if (!query) {
            return res.status(400).json({ status: 'error', message: 'Missing query parameter.' });
        }

        const output = await extractor(query, { pooling: 'mean', normalize: true });
        const queryVector = Array.from(output.data);

        const storageDir = path.join(__dirname, 'vault_storage');
        if (!fs.existsSync(storageDir)) {
            return res.json({ status: 'success', query_evaluated: query, results_found: 0, matches: [] });
        }

        const files = fs.readdirSync(storageDir);
        const matches = [];

        for (const file of files) {
            if (!file.endsWith('.json')) continue;

            const filePath = path.join(storageDir, file);
            const fileData = JSON.parse(fs.readFileSync(filePath, 'utf8'));

            const similarity = cosineSimilarity(queryVector, fileData.vector);

            // 📊 MIN-MAX NORMALIZATION ENGINE
            const minExpectedBoundary = 0.15;
            const maxExpectedBoundary = 0.65;
            let normalizedPercentage = ((similarity - minExpectedBoundary) / (maxExpectedBoundary - minExpectedBoundary)) * 100;
            
            if (normalizedPercentage < 0) normalizedPercentage = 0.0;
            if (normalizedPercentage > 100) normalizedPercentage = 100.0;
            
            const finalRelevanceScore = parseFloat(normalizedPercentage.toFixed(1));

            matches.push({
                filename: fileData.original_name,
                similarity_score: similarity,
                relevance_percentage: finalRelevanceScore,
                timestamp: fileData.timestamp
            });
        }

        matches.sort((a, b) => b.relevance_percentage - a.relevance_percentage);

        res.json({
            status: 'success',
            query_evaluated: query,
            results_found: matches.length,
            matches: matches
        });

    } catch (error) {
        res.status(500).json({ status: 'error', message: error.message });
    }
});

app.get('/health', (req, res) => {
    res.json({ status: 'healthy', service: 'storage-service' });
});

app.listen(PORT, () => {
    console.log(`=================================================`);
    console.log(`🚀 STORAGE ENGINE ACTIVE ON PORT: ${PORT}`);
    console.log(`=================================================`);
});

// =================================================================
// 🛡️ SECURE STEGANOGRAPHY FILE INGESTION PIPELINE
// =================================================================
const SecurityEngine = require('./security_engine');

app.post('/api/v1/storage/ingest', async (req, res) => {
    try {
        const { filename, content } = req.body;
        if (!filename || !content) {
            return res.status(400).json({ status: 'error', message: 'Missing filename or content payload.' });
        }

        console.log(`🛡️ Ingestion Engine processing document pipeline for: ${filename}`);

        // 1. Generate the vector mathematical embedding coordinates for semantic searching
        const output = await extractor(content, { pooling: 'mean', normalize: true });
        const vectorArray = Array.from(output.data);

        // 2. Convert text content to raw bytes and pass through our bitwise masking engine
        const rawBuffer = Buffer.from(content, 'utf8');
        const maskedBuffer = SecurityEngine.applyMask(rawBuffer);

        const storageDir = path.join(__dirname, 'vault_storage');
        if (!fs.existsSync(storageDir)) {
            fs.mkdirSync(storageDir, { recursive: true });
        }

        // Save the vector data alongside the obfuscated content payload inside the secure JSON container
        const fileRecordName = `${Date.now()}-${filename.replace('.txt', '')}.json`;
        const completePayload = {
            original_name: filename,
            vector: vectorArray,
            masked_payload: maskedBuffer.toString('base64'), // Stored cleanly as an encoded string
            timestamp: new Date().toISOString()
        };

        fs.writeFileSync(path.join(storageDir, fileRecordName), JSON.stringify(completePayload, null, 2));
        console.log(`✅ Secure payload locked down cleanly onto system disk: ${fileRecordName}`);

        res.json({
            status: 'secured',
            vault_file: fileRecordName,
            message: 'Academic document successfully obfuscated and indexed into vector space.'
        });

    } catch (error) {
        console.error("Secure Ingestion Pipeline Breakdown:", error);
        res.status(500).json({ status: 'error', message: error.message });
    }
});

// =================================================================
// 🔓 SECURE STEGANOGRAPHY FILE DECRYPTION / RETRIEVAL PIPELINE
// =================================================================
app.post('/api/v1/storage/retrieve', async (req, res) => {
    try {
        const { vault_file } = req.body;
        if (!vault_file) {
            return res.status(400).json({ status: 'error', message: 'Missing target vault filename parameter.' });
        }

        const filePath = path.join(__dirname, 'vault_storage', vault_file);
        
        // Safety check to verify if the document actually exists in the local block repository
        if (!fs.existsSync(filePath)) {
            return res.status(404).json({ status: 'error', message: 'Target secure file metadata node not found.' });
        }

        // 1. Read the raw structural JSON container from system disk storage
        const rawData = fs.readFileSync(filePath, 'utf8');
        const fileRecord = JSON.parse(rawData);

        // 2. Extract the base64 string back into an obfuscated byte buffer
        const maskedBuffer = Buffer.from(fileRecord.masked_payload, 'base64');

        // 3. Reverse the symmetric XOR mask matrix using our Security Engine
        const decryptedBuffer = SecurityEngine.removeMask(maskedBuffer);

        // 4. Transform pristine bytes back into a native human-readable string layout
        const originalContent = decryptedBuffer.toString('utf8');

        console.log(`🔓 Decryption matrix successfully decoded payload for client: ${fileRecord.original_name}`);

        res.json({
            status: 'decrypted',
            filename: fileRecord.original_name,
            content: originalContent,
            timestamp: fileRecord.timestamp
            
        });

    } catch (error) {
        console.error("Secure Retrieval Pipeline Breakdown:", error);
        res.status(500).json({ status: 'error', message: error.message });
    }
});
