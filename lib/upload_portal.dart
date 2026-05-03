import 'dart:io';
import 'dart:convert';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'package:image_picker/image_picker.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:google_generative_ai/google_generative_ai.dart';
import 'package:syncfusion_flutter_pdf/pdf.dart';
import 'ranking_engine.dart';
import 'security_engine.dart';
import 'network_discovery.dart';

class UploadPortal extends StatefulWidget {
  const UploadPortal({super.key});

  @override
  State<UploadPortal> createState() => _UploadPortalState();
}

class _UploadPortalState extends State<UploadPortal> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _departmentController = TextEditingController();
  
  bool _isSecureAccess = false;
  bool _isProjectShowcase = false;
  File? _coverImage;
  File? _academicFile;
  bool _isValidating = false;

  final String _syllabusContext = "Core Focus: Information Retrieval, Vector Space Models, "
      "Database Optimization, B-Trees, Expert Systems Architecture.";

  Future<void> _pickCover() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery);
    if (picked != null) {
      // Check for HEIC format which causes pixel-weaving failures
      if (picked.path.toLowerCase().endsWith('.heic')) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
            content: Text("INVALID_FORMAT: .HEIC detected. Steganography requires PNG or JPG."),
            backgroundColor: Colors.redAccent,
          ));
        }
        return;
      }
      setState(() => _coverImage = File(picked.path));
    }
  }

  Future<void> _pickFile() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf'],
    );
    if (result != null) setState(() => _academicFile = File(result.files.single.path!));
  }

  Future<void> _certifyKnowledge() async {
    if (!_formKey.currentState!.validate()) return;
    if (_academicFile == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Please attach the learning material (PDF)")));
      return;
    }

    setState(() => _isValidating = true);

    try {
      double matchRate = 0.5;
      String? extractedText;

      // 1. ACADEMIC INTEGRITY SCAN (AI AUDITOR)
      final PdfDocument document = PdfDocument(inputBytes: _academicFile!.readAsBytesSync());
      extractedText = PdfTextExtractor(document).extractText();
      document.dispose();

      if (extractedText.trim().isEmpty) {
        throw Exception("EMPTY_DOCUMENT: The PDF contains no extractable text. Please use a document with searchable text.");
      }

      const apiKey = "use your api key hear";
      // Synchronized with Fiscal-A: Using Gemini 2.5 Flash as requested
      final model = GenerativeModel(model: 'gemini-2.5-flash', apiKey: apiKey);

      // 1.5 CARRIER IMAGE PROTOCOL (STRICT SCAN)
      if (_isSecureAccess && _coverImage != null) {
        debugPrint("AI_SCAN: Analyzing Carrier Image for Academic Integrity...");
        final imageBytes = await _coverImage!.readAsBytes();
        final imagePrompt = [
          Content.multi([
            TextPart("TASK: Image Safety & Privacy Audit. Evaluate if this image is suitable as a carrier for academic data. "
                "ALLOWED: Most neutral photos, including nature, landscapes, and campus environments. "
                "STRICTLY FORBIDDEN: "
                "1. Clear faces or identifiable people (Privacy protection). "
                "2. Violent, disturbing, or gore content. "
                "3. Overtly religious symbols or propaganda. "
                "4. Political figures, slogans, or highly controversial imagery. "
                "5. Text that is offensive or violates academic ethics. "
                "If the image is neutral and safe, 'isProfessional' must be true. If it contains any forbidden elements, 'isProfessional' must be false. "
                "Return ONLY JSON: {\"isProfessional\": bool, \"reason\": \"brief explanation\"}."),
            DataPart('image/jpeg', imageBytes),
          ])
        ];

        try {
          final imageResponse = await model.generateContent(imagePrompt).timeout(const Duration(seconds: 15));
          final imageJson = json.decode(imageResponse.text?.replaceAll(RegExp(r'```json|```'), '').trim() ?? "{\"isProfessional\": true, \"reason\": \"Unknown\"}");
          
          if (imageJson['isProfessional'] == false) {
            throw Exception("IMAGE_REJECTED: ${imageJson['reason'] ?? 'The carrier image does not meet Academic Integrity standards.'}");
          }
        } catch (e) {
          if (e.toString().contains("IMAGE_REJECTED")) rethrow;
          debugPrint("IMAGE_SCAN_BYPASS: AI Timeout or Error. Proceeding with caution.");
        }
      }

      try {
        final prompt = "SYSTEM: Academic Integrity Auditor. SYLLABUS: [$_syllabusContext]. "
            "TEXT: ${extractedText.substring(0, extractedText.length > 3000 ? 3000 : extractedText.length)}. "
            "TASK: Return ONLY JSON: {\"relevance\": 0.0-1.0, \"isAcademic\": bool}. "
            "IMPORTANT: If text is not academic or is blank/random symbols, isAcademic must be false.";

        final response = await model.generateContent([Content.text(prompt)]).timeout(const Duration(seconds: 15));
        final responseText = response.text;
        
        if (responseText == null || responseText.isEmpty) throw Exception("Empty AI Response");

        final cleanJson = responseText.replaceAll(RegExp(r'```json|```'), '').trim();
        final jsonResult = json.decode(cleanJson);
        
        if (jsonResult['isAcademic'] == false) {
          throw Exception("Material rejected: Content does not align with academic integrity standards.");
        }
        matchRate = (jsonResult['relevance'] as num).toDouble();
      } catch (aiErr) {
        // 3. GRACEFUL FALLBACK (Handles SocketException, Timeouts, or Model issues)
        debugPrint("AI_DISFUNCTION: $aiErr. Switching to local ranking math.");
        double localSim = RankingEngine.calculateSimilarity(_syllabusContext, extractedText);
        
        // We use the local mathematical similarity as the match rate
        matchRate = localSim > 0.1 ? localSim : 0.45;
        
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text("AI Labeled Busy ($aiErr): Utilizing local Vector-Space math for ranking."),
            backgroundColor: Colors.indigo,
            duration: const Duration(seconds: 5),
          ));
        }
      }

      // 2. VAULT ENCRYPTION (If selected)
      String? vaultPath;
      if (_isSecureAccess && _coverImage != null) {
        final vaultFile = await SteganoEngine.weaveKnowledge(
          carrierImage: _coverImage!, 
          payloadDoc: _academicFile!
        );
        vaultPath = vaultFile.path;
      } else {
        vaultPath = _academicFile!.path;
      }

      // 3. PERSISTENCE
      final box = Hive.box('knowledge_vault');
      final newInsight = {
        'id': DateTime.now().millisecondsSinceEpoch.toString(),
        'title': _titleController.text,
        'author': "Authorised Faculty",
        'topic': _departmentController.text,
        'relevanceScore': matchRate,
        'isEncrypted': _isSecureAccess,
        'isMarketplace': _isProjectShowcase,
        'vaultPath': vaultPath,
        'textContent': extractedText.substring(0, math.min(500, extractedText.length)),
      };

      final List currentItems = box.get('items', defaultValue: []);
      currentItems.add(newInsight);
      await box.put('items', currentItems);

      // 4. DISTRIBUTED STORAGE (The "Pied Piper" Protocol)
      if (_isProjectShowcase) {
        try {
          final pdfBytes = await _academicFile!.readAsBytes();
          final shards = SteganoEngine.shardKnowledge(pdfBytes, shardCount: 5);
          final peers = PeerDiscovery().discoveredPeers;
          
          int distributedCount = 0;
          for (int i = 0; i < shards.length; i++) {
            if (peers.isNotEmpty) {
              final peer = peers[i % peers.length];
              final success = await PeerDiscovery().pushShardToPeer(
                peer.host!, peer.port!, "${newInsight['id']}_$i", shards[i]
              );
              if (success) distributedCount++;
            }
          }
          debugPrint("PIED_PIPER_PROTOCOL_COMPLETE: Knowledge sharded and decentralized.");
          debugPrint("STATUS: $distributedCount shards hosted across the campus mesh network.");
        } catch (p2pErr) {
          debugPrint("P2P_DISTRIBUTION_FAILED: $p2pErr");
        }
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(_isProjectShowcase 
            ? "Knowledge Certified & Sharded across the Peer Network" 
            : "Knowledge Certified and Shared locally"),
          backgroundColor: Colors.indigo,
        ));
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Certification Error: $e")));
    } finally {
      setState(() => _isValidating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text("CONTRIBUTE KNOWLEDGE", style: TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: Colors.indigo)),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(30),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _header("Primary Details"),
              _input(_titleController, "Material Title", "e.g. Intro to Vector Models"),
              const SizedBox(height: 15),
              _input(_departmentController, "Subject / Domain", "e.g. Computer Science"),
              
              const SizedBox(height: 35),
              _header("Access Protocol"),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text("Verified Privacy Mode", style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                subtitle: const Text("Hides material within a cover image", style: TextStyle(fontSize: 11)),
                value: _isSecureAccess,
                activeThumbColor: Colors.indigo,
                onChanged: (v) => setState(() => _isSecureAccess = v),
              ),
              if (_isSecureAccess) ...[
                const SizedBox(height: 10),
                _filePicker("Select Cover Image", _coverImage?.path.split('/').last ?? "No image selected", Icons.image, _pickCover),
              ],
              
              const SizedBox(height: 10),
              _filePicker("Attach Academic Document", _academicFile?.path.split('/').last ?? "No PDF selected", Icons.picture_as_pdf, _pickFile),
              
              const SizedBox(height: 10),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text("Project Showcase", style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                subtitle: const Text("Feature this as a student technical project", style: TextStyle(fontSize: 11)),
                value: _isProjectShowcase,
                activeThumbColor: Colors.deepPurple,
                onChanged: (v) => setState(() => _isProjectShowcase = v),
              ),

              const SizedBox(height: 40),
              SizedBox(
                width: double.infinity,
                height: 60,
                child: Container(
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Colors.indigo, Colors.deepPurple],
                      begin: Alignment.centerLeft,
                      end: Alignment.centerRight,
                    ),
                    borderRadius: BorderRadius.circular(15),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.indigo.withValues(alpha: 0.3),
                        blurRadius: 15,
                        offset: const Offset(0, 8),
                      )
                    ],
                  ),
                  child: ElevatedButton(
                    onPressed: _isValidating ? null : _certifyKnowledge,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.transparent,
                      foregroundColor: Colors.white,
                      shadowColor: Colors.transparent,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                      elevation: 0,
                    ),
                    child: _isValidating 
                      ? Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const SizedBox(
                              width: 18, height: 18,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            ),
                            const SizedBox(height: 8),
                            Text(_isSecureAccess ? "AUDITING_VAULT..." : "ANALYZING_CONTENT...", 
                              style: const TextStyle(fontSize: 8, fontWeight: FontWeight.bold, letterSpacing: 2)),
                          ],
                        )
                      : const Text("CERTIFY AND BROADCAST", style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 2, fontSize: 13)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _header(String t) => Padding(
    padding: const EdgeInsets.only(bottom: 12, left: 5),
    child: Row(
      children: [
        Container(width: 4, height: 14, decoration: BoxDecoration(color: Colors.indigo, borderRadius: BorderRadius.circular(2))),
        const SizedBox(width: 10),
        Text(t.toUpperCase(), style: const TextStyle(color: Colors.black87, fontSize: 11, fontWeight: FontWeight.w900, letterSpacing: 1.5)),
      ],
    ),
  );

  Widget _input(TextEditingController c, String l, String h) => Container(
    decoration: BoxDecoration(
      boxShadow: [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.02),
          blurRadius: 10,
          offset: const Offset(0, 4),
        )
      ],
    ),
    child: TextFormField(
      controller: c,
      validator: (v) => v!.isEmpty ? "Required" : null,
      style: const TextStyle(fontSize: 14),
      decoration: InputDecoration(
        labelText: l, hintText: h,
        labelStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.indigo),
        hintStyle: const TextStyle(fontSize: 12, color: Colors.black26),
        filled: true, fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: const BorderSide(color: Colors.indigo, width: 1.5)),
      ),
    ),
  );

  Widget _filePicker(String l, String v, IconData i, VoidCallback t) => InkWell(
    onTap: t,
    borderRadius: BorderRadius.circular(15),
    child: Container(
      padding: const EdgeInsets.all(15),
      margin: const EdgeInsets.symmetric(vertical: 5),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(15), border: Border.all(color: Colors.black12)),
      child: Row(children: [
        Icon(i, color: Colors.indigo, size: 20),
        const SizedBox(width: 15),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(l, style: const TextStyle(fontSize: 10, color: Colors.black45)),
          Text(v, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold), overflow: TextOverflow.ellipsis),
        ])),
      ]),
    ),
  );
}
