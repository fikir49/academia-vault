# Academia Vault - AI & Logic Architecture Context

## 🎯 System Mission
To provide a **Distributed Intelligence Protocol** for university-grade materials, shifting the paradigm from "Centralized Storage" to "Localized Peer-to-Peer Knowledge Streams."

## 🧩 Architectural Pillars

### 1. The Ranking Engine (Mathematical IR)
- **Algorithm**: Vector Space Model (VSM) using Cosine Similarity.
- **Logic**: It converts both the "Global Syllabus" and document "Text Shards" into frequency vectors. The scalar product of these vectors determines the `relevanceScore` (0.0 - 1.0).
- **Implementation**: See `lib/ranking_engine.dart`.

### 2. The Stegano-Security Engine
- **Encryption**: AES-256 (CBC Mode) for raw data.
- **Hiding Mechanism**: LSB (Least Significant Bit) manipulation. It hides encrypted bytes within the RGB channels of a carrier image.
- **Audit Requirement**: Every carrier image undergoes an AI-driven safety scan to prevent the use of inappropriate or privacy-violating "vault covers."

### 3. Decentralized Mesh (P2P)
- **Discovery**: NSD (Network Service Discovery) / mDNS.
- **Protocol**: Custom TCP-based sharding. When a user marks a project for "Showcase," it is sharded into 5-10 fragments and pushed to discovered peers.
- **Persistence**: Relies on "Peer Availability." The network health UI reflects the current redundancy level.

## 🤖 AI Configuration & Ethics
- **Model**: `gemini-2.5-flash` (Optimized for speed/latency).
- **Integration**: `Google Generative AI` SDK.
- **Audit Roles**:
    1. **Content Validity**: Rejects non-academic or low-quality noise.
    2. **Image Safety**: Ensures carrier images are "Academic Professional" (e.g., landscape, abstract, architecture) and free of PII (Personally Identifiable Information).
    3. **Semantic Mapping**: Provides a high-level summary of the document for IR metadata.

## 🛠 Developer Notes
- **Placeholder DNA**: Items with `id: 1` and `id: 2` in `lib/main.dart` are hardcoded for demonstration. They simulate the "Ideal Stream" state.
- **API Key Management**: The `apiKey` in `lib/upload_portal.dart` is a placeholder. Users must provide their own key to activate AI-auditing features.
- **Mock Fallback**: If the AI API fails (quota/network), the system gracefully degrades to **Local Vector-Space Math** for ranking, ensuring the app remains functional offline.
