import 'dart:io';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'package:image_picker/image_picker.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:google_generative_ai/google_generative_ai.dart';
import 'package:syncfusion_flutter_pdf/pdf.dart';
import 'security_engine.dart';

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
    if (picked != null) setState(() => _coverImage = File(picked.path));
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

      const apiKey = "AIzaSyDXjEYDebAYE66vg9wYON-sa8qApNTsYAE"; 
      final model = GenerativeModel(model: 'gemini-1.5-flash', apiKey: apiKey);
      
      final prompt = "SYSTEM: Academic Integrity Auditor. SYLLABUS: [$_syllabusContext]. "
          "TEXT: ${extractedText.substring(0, extractedText.length > 3000 ? 3000 : extractedText.length)}. "
          "TASK: Return ONLY JSON: {\"relevance\": 0.0-1.0, \"isAcademic\": bool}.";

      final response = await model.generateContent([Content.text(prompt)]);
      final jsonResult = json.decode(response.text?.replaceAll('```json', '').replaceAll('```', '') ?? "{\"relevance\": 0.5, \"isAcademic\": true}");
      
      if (jsonResult['isAcademic'] == false) {
        throw Exception("Material rejected: Content does not align with academic integrity standards.");
      }
      matchRate = (jsonResult['relevance'] as num).toDouble();

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
        'textContent': extractedText.substring(0, 500),
      };

      final List currentItems = box.get('items', defaultValue: []);
      currentItems.add(newInsight);
      await box.put('items', currentItems);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Knowledge Certified and Shared with the Network")));
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
                child: ElevatedButton(
                  onPressed: _isValidating ? null : _certifyKnowledge,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.indigo,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                    elevation: 0,
                  ),
                  child: _isValidating 
                    ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                    : const Text("CERTIFY AND SHARE", style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _header(String t) => Padding(
    padding: const EdgeInsets.only(bottom: 15),
    child: Text(t.toUpperCase(), style: const TextStyle(color: Colors.indigo, fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 1)),
  );

  Widget _input(TextEditingController c, String l, String h) => TextFormField(
    controller: c,
    validator: (v) => v!.isEmpty ? "Required" : null,
    decoration: InputDecoration(
      labelText: l, hintText: h,
      labelStyle: const TextStyle(fontSize: 12),
      filled: true, fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: const BorderSide(color: Colors.black12)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: const BorderSide(color: Colors.indigo, width: 1)),
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
