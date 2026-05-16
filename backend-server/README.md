# 🏛️ Academia Vault: The IR Intelligence Engine

<p align="center">
  <b>A Decentralized Information Retrieval System for Bahir Dar University</b>
</p>

## 🎯 Project Overview
Academia Vault is a specialized backend server designed to bridge the gap between technical academic modules and student needs. Unlike standard search engines, this "Brain" uses **Inverted Indexing** and **Linguistic Mapping** to provide context-aware technical definitions in both Amharic and English.

## 🚀 Specialized IR Features
- **Smart Inverted Indexing:** A custom PHP engine that parses PDFs, calculates Term Frequency (TF), and extracts the exact sentence where a technical term is defined.
- **Cross-Language Retrieval (CLIR):** Translates Amharic queries (e.g., *ተዛማጅነት*) to English technical terms (e.g., *relevance*) to search the Vault.
- **Identity-Based Ranking:** Results are dynamically re-ranked based on the user's Department (e.g., Information Systems vs. Engineering).
- **Resource Optimized:** Engineered to run high-performance searches on a **4GB RAM** environment using SQLite's indexing power.

## 🏗️ Technical Architecture
### 1. Folder Structure
- `app/Console/Commands/`: Contains `vault:index` (The PDF parsing engine).
- `app/Models/`: Handles data normalization for the Inverted Index and Lexicon.
- `database/database.sqlite`: The encrypted, binary-safe data store.
- `storage/app/public/`: The decentralized storage for technical PDFs.

### 2. The Logic Layer
The backend utilizes a **RESTful API** architecture. When a search is performed, the server executes:
1. **Normalization:** Cleaning the query and removing stopwords.
2. **Lexical Mapping:** Checking the dictionary for generic definitions.
3. **Vault Scan:** Pulling technical snippets from indexed PDFs.
4. **ID-Header Filter:** Applying student-specific priority ranking.

## 🛠️ Commands for Review
To test the system logic directly from the terminal:
```bash
# Refresh the technical index from PDFs
php artisan vault:index

# Enter the logic shell to query data
php artisan tinker

# Show database health and table statistics
php artisan db:show