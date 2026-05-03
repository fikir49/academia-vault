# 🛠 Academia Vault: Protocol Specifications

This document provides a technical deep-dive into the core engines that power the **Academia Vault** ecosystem.

---

## 1. The Semantic Ranking Engine (Mathematics)

The system uses a **Vector Space Model (VSM)** to determine how well a document matches the "Global Syllabus."

### 📐 The Math: Cosine Similarity
Every document ($d$) and the syllabus ($s$) are transformed into vectors in a multi-dimensional space where each dimension represents a unique term.

The similarity score is calculated using the formula:
$$\text{Similarity} = \cos(\theta) = \frac{\mathbf{A} \cdot \mathbf{B}}{\|\mathbf{A}\| \|\mathbf{B}\|}$$

**How it works in the app:**
1.  **Tokenization:** The `RankingEngine` strips symbols, lowercases text, and filters out "stop-words" (words < 3 letters).
2.  **Frequency Mapping:** It creates a Term Frequency (TF) map for both texts.
3.  **Dot Product:** It calculates the magnitude of match across the overlapping vocabulary.
4.  **Result:** A value between `0.0` (no match) and `1.0` (perfect match).

---

## 2. Invisible Security (Steganography)

The `SecurityEngine` implements **Least Significant Bit (LSB)** insertion, a form of steganography.

### 🎨 Pixel Weaving Logic
Digital images are made of pixels, each with Red, Green, and Blue (RGB) values ranging from 0-255.
*   The number `254` (binary `11111110`) looks identical to `255` (binary `11111111`) to the human eye.
*   The Vault uses this "invisible" bit to store encrypted data.

**The Pipeline:**
1.  **Encryption:** Raw PDF bytes are encrypted using **AES-256 (CBC)**.
2.  **Compression:** The encrypted data is G-Zipped to reduce the bit-footprint.
3.  **Insertion:** The binary stream is "woven" into the last bit of every RGB channel across the carrier image.
4.  **Length Header:** The first 32 pixels store the total size of the hidden payload so the reassembler knows when to stop.

---

## 3. Distributed Sharding (P2P Mesh)

The **Pied Piper Protocol** ensures data persistence without a central server.

### 🧩 Sharding & Redundancy
When a user shares a technical project, the `SteganoEngine` executes the **Shredder Logic**:
1.  **Splitting:** The binary payload is split into $N$ fragments (default 5).
2.  **Integrity Header:** Each shard is prepended with a 9-byte header:
    - `[0]`: Shard Index (used for reassembly order).
    - `[1-4]`: Segment Length.
    - `[5-8]`: CRC-32 Checksum (to verify the shard hasn't been corrupted during P2P transfer).
3.  **Discovery:** The `PeerDiscovery` node uses mDNS to broadcast its presence and find peers on port `5000`.
4.  **Distribution:** Shards are pushed to different nodes. To reconstruct the file, the app must "collect" at least one copy of every index from the network.

---

## 4. The AI Auditor Protocol

The Gemini 2.5 Flash model is prompted with a strict **System Instruction Set**:
- **Role**: Academic Integrity Auditor.
- **Constraints**: Rejects non-academic text, identifies privacy violations in images, and generates a semantic summary for the IR metadata.
- **Graceful Degradation**: If the API is unavailable, the system defaults to the local mathematical `RankingEngine` to ensure uptime.

---

**Developed for Academia Vault** | *Fikir Wendmnew*
