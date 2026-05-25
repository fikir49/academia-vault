const fs = require('fs');
const path = require('path');

const DB_PATH = path.join(__dirname, 'vector_store.json');

// Ensure the JSON database file exists natively on the disk box
if (!fs.existsSync(DB_PATH)) {
    fs.writeFileSync(DB_PATH, JSON.stringify([]));
}

/**
 * Calculates the cosine similarity between two 384-dimensional vector arrays.
 * Since our Xenova model normalizes vectors, this simplifies down to a dot product.
 */
function calculateSimilarity(vectorA, vectorB) {
    if (vectorA.length !== vectorB.length) return 0;
    let dotProduct = 0;
    for (let i = 0; i < vectorA.length; i++) {
        dotProduct += vectorA[i] * vectorB[i];
    }
    return dotProduct;
}

/**
 * Saves a document's vector metadata record into the physical JSON store.
 */
function saveVectorRecord(filename, vector) {
    const records = JSON.parse(fs.readFileSync(DB_PATH, 'utf-8'));
    
    // Evict any existing old records for this exact file to avoid duplication loops
    const filteredRecords = records.filter(rec => rec.filename !== filename);
    
    filteredRecords.push({
        filename,
        vector,
        timestamp: new Date().toISOString()
    });
    
    fs.writeFileSync(DB_PATH, JSON.stringify(filteredRecords, null, 2));
}

module.exports = {
    saveVectorRecord,
    calculateSimilarity,
    DB_PATH
};
/**
 * Compares a query vector against all stored vectors and returns the closest matches.
 */
function findNearestNeighbors(queryVector, limit = 5) {
    const records = JSON.parse(fs.readFileSync(DB_PATH, 'utf-8'));
    
    // Map through records and calculate the similarity score for each item
    const scoredRecords = records.map(record => {
        const score = calculateSimilarity(queryVector, record.vector);
        return {
            filename: record.filename,
            similarity_score: score,
            timestamp: record.timestamp
        };
    });

    // Sort descending: closest match (highest score near 1.0) moves to the top
    return scoredRecords
        .sort((a, b) => b.similarity_score - a.similarity_score)
        .slice(0, limit);
}

// Make sure to export the new function alongside the others!
module.exports = {
    saveVectorRecord,
    calculateSimilarity,
    findNearestNeighbors,
    DB_PATH
};