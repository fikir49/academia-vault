# 🏛 Academia Vault: The Intelligent Knowledge Stream

[![Flutter](https://img.shields.io/badge/Flutter-%2302569B.svg?style=for-the-badge&logo=Flutter&logoColor=white)](https://flutter.dev)
[![Dart](https://img.shields.io/badge/dart-%230175C2.svg?style=for-the-badge&logo=dart&logoColor=white)](https://dart.dev)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)

**Academia Vault** is a decentralized, AI-augmented academic ecosystem built with Flutter. It moves beyond static file storage to create a **"Living Knowledge Mesh"**—where academic materials are semantically ranked, securely hidden, and distributed across a peer-to-peer network without the need for a central server.

---

## ✨ Evolutionary Features

### 🧠 1. Semantic Ranking Engine (V.S.M.)
Unlike traditional search engines that rely on keyword matching, Academia Vault understands the **context** of your studies.
*   **Vector Space Modeling:** Every document is converted into a mathematical vector. The app calculates the **Cosine Similarity** between your current syllabus and the document's "Knowledge DNA."
*   **AI-Driven Integrity:** Integrated with **Gemini 2.5 Flash**, the app acts as an automated "Academic Auditor," verifying that uploaded materials are relevant, ethical, and high-quality before they enter the stream.

### 🔐 2. Invisible Vaults (LSB Steganography)
True privacy means no one even knows your data exists.
*   **Pixel-Level Encryption:** Sensitive files are encrypted via **AES-256** and then woven into the **Least Significant Bits (LSB)** of standard images (PNG/JPG).
*   **Carrier Protocol:** The AI-Auditor scans potential "cover images" for privacy violations (like faces or PII) before allowing them to be used as data vaults.
*   **Zero-Footprint:** Your academic PDFs are hidden inside harmless-looking photos in your gallery.

### 🌐 3. Decentralized "Pied Piper" Network
Built for resilience in campus environments where internet might be restricted.
*   **mDNS Peer Discovery:** Uses **Network Service Discovery (NSD)** to find other scholars on the same Wi-Fi/LAN automatically.
*   **Shard Distribution:** Projects are broken into mathematical shards and distributed across available nodes. This ensures that even if one device goes offline, the knowledge remains accessible through the mesh.
*   **P2P Protocol:** A custom TCP-based stream handles the transfer of binary fragments between devices in real-time.

### ⚡ 4. High-Performance Local Intelligence
*   **Hive NoSQL Persistence:** Optimized for sub-millisecond data retrieval.
*   **Biometric Authorization:** The vault protocol only triggers de-pixelation after a successful fingerprint or face scan.
*   **Offline Maturity:** All mathematical ranking and secure storage logic works 100% offline.

---

## 📖 Deep Dives
To understand the underlying mathematics and logic of this project, please refer to our supplemental documentation:

*   **[PROTOCOL_SPEC.md](./PROTOCOL_SPEC.md)**: A deep dive into the VSM Math, Steganographic Algorithms, and P2P Sharding Logic.
*   **[AI_CONTEXT.md](./AI_CONTEXT.md)**: Details on the Gemini Auditor's prompt engineering and ethical constraints.

---

## 🚀 Getting Started

1.  **Clone & Fetch:**
    ```bash
    git clone https://github.com/fikir49/academia-vault.git
    flutter pub get
    ```
2.  **API Configuration:**
    Navigate to `lib/upload_portal.dart` and insert your Gemini API Key in the `apiKey` field.
3.  **Build Release:**
    ```bash
    flutter build apk --release --obfuscate --split-debug-info=build/app/outputs/symbols
    ```

---

## 📜 License & Acknowledgments
Distributed under the **MIT License**. Created by **FIKIR WENDMNEW** as a vision for the future of decentralized academic intelligence.
