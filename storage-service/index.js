const express = require('express');
const cors = require('cors');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 5000;

app.use(cors());
app.use(express.json());

// 1. Configure Persistent Disk Storage Box Location
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        const uploadPath = path.join(__dirname, 'vault_storage');
        if (!fs.existsSync(uploadPath)) {
            fs.mkdirSync(uploadPath, { recursive: true });
        }
        cb(null, uploadPath);
    },
    filename: (req, file, cb) => {
        cb(null, `${Date.now()}-${file.originalname}`);
    }
});

// 2. File Guard Filter (Accept only PDFs and text assets)
const fileFilter = (req, file, cb) => {
    if (file.mimetype === 'application/pdf' || file.mimetype === 'text/plain') {
        cb(null, true);
    } else {
        cb(new Error('Security Lockout: Vault storage layer only accepts PDF and TXT documents.'), false);
    }
};

const upload = multer({ storage: storage, fileFilter: fileFilter });

// 3. Document Stream Ingestion Endpoint
app.post('/api/v1/storage/upload', upload.single('academic_file'), (req, res) => {
    try {
        if (!req.file) {
            return res.status(400).json({ error: 'Please attach a valid document asset payload.' });
        }
        res.status(201).json({
            status: 'stored',
            message: 'Academic document ingested into secure storage vault.',
            file_meta: {
                filename: req.file.filename,
                size_bytes: req.file.size,
                internal_path: req.file.path
            }
        });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// 4. Semantic Parsing Gatekeeper Integration (AI Pipeline Node)
app.post('/api/v1/storage/analyze-semantics', (req, res) => {
    const { filename } = req.body;
    if (!filename) return res.status(400).json({ error: 'Target filename required for AI evaluation matrix.' });

    const mockSemanticVector = Array.from({ length: 384 }, () => Math.random() * 2 - 1);

    res.json({
        status: 'analyzed',
        target_asset: filename,
        classification: 'Academic Assignment / Curriculum Document',
        security_clearance: 'PASSED',
        semantic_vector_sample: mockSemanticVector.slice(0, 5)
    });
});

app.get('/health', (req, res) => {
    res.json({ status: 'online', service: 'Storage Node', timestamp: new Date().toISOString() });
});

app.listen(PORT, () => {
    console.log(`=================================================`);
    console.log(`🚀 STORAGE ENGINE ACTIVE ON PORT: ${PORT}`);
    console.log(`=================================================`);
});