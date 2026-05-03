import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:local_auth/local_auth.dart';
import 'dart:io';
import 'dart:math' as math;
import 'dart:typed_data';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'upload_portal.dart';
import 'ranking_engine.dart';
import 'security_engine.dart';
import 'network_discovery.dart';

// ACADEMIA VAULT - THE INTELLIGENT KNOWLEDGE STREAM
enum UserStatus { foundation, contributor, administrator }

class LearningInsight {
  final String id;
  final String title;
  final String provider;
  final String department;
  final double matchRate;
  final bool isPrivate;
  final bool isProject;
  final String? textContent; // For IR math
  final String? vaultPath;

  LearningInsight({
    required this.id, required this.title, required this.provider,
    required this.department, this.matchRate = 0.0,
    this.isPrivate = false, this.isProject = false,
    this.textContent, this.vaultPath,
  });

  factory LearningInsight.fromMap(Map<dynamic, dynamic> map) => LearningInsight(
    id: map['id'], title: map['title'], provider: map['author'] ?? "Academic Node",
    department: map['topic'] ?? "General", matchRate: map['relevanceScore'] ?? 0.0,
    isPrivate: map['isEncrypted'] ?? false, isProject: map['isMarketplace'] ?? false,
    textContent: map['textContent'], vaultPath: map['vaultPath'],
  );

  Map<String, dynamic> toMap() => {
    'id': id, 'title': title, 'author': provider, 'topic': department,
    'relevanceScore': matchRate, 'isEncrypted': isPrivate,
    'isMarketplace': isProject, 'textContent': textContent,
    'vaultPath': vaultPath,
  };
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Hive.initFlutter();
  await Hive.openBox('knowledge_vault');
  runApp(const AcademiaVault());
}

class AcademiaVault extends StatelessWidget {
  const AcademiaVault({super.key});
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        fontFamily: 'sans-serif',
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.indigo, brightness: Brightness.light),
      ),
      home: const KnowledgeStream(),
    );
  }
}

class KnowledgeStream extends StatefulWidget {
  const KnowledgeStream({super.key});
  @override
  State<KnowledgeStream> createState() => _KnowledgeStreamState();
}

class _KnowledgeStreamState extends State<KnowledgeStream> with TickerProviderStateMixin {
  final _vaultBox = Hive.box('knowledge_vault');
  final LocalAuthentication _auth = LocalAuthentication();
  final PeerDiscovery _peerDiscovery = PeerDiscovery();
  final TextEditingController _searchController = TextEditingController();
  late AnimationController _pulseController;
  
  List<LearningInsight> _insights = [];
  UserStatus _currentStatus = UserStatus.foundation;
  bool _isSidebarOpen = false;
  bool _isRanking = false;
  String _selectedDept = "All";
  String _searchQuery = "";
  
  // REAL P2P NETWORK DATA
  int _activeNodesCount = 0;
  int _shardsHostingCount = 0;
  double _networkHealth = 0.0;
  final Set<String> _discoveredNodeIds = {};
  final Set<String> _savedItemIds = {};
  final List<String> _history = [];
  bool _showingSavedOnly = false;

  final List<String> _departments = ["All", "Computer Science", "Engineering", "Mathematics", "Physics", "Economics"];

  String _currentSyllabus = "Core Focus: Information Retrieval, Vector Space Models, "
      "Database Optimization, B-Trees, Expert Systems Architecture.";

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(vsync: this, duration: const Duration(seconds: 2))..repeat(reverse: true);
    _refreshStream();
    _initP2PNetwork();
  }

  Future<void> _initP2PNetwork() async {
    final nodeId = DateTime.now().millisecondsSinceEpoch.toString().substring(7);
    await _peerDiscovery.startNode(nodeId);
    
    _peerDiscovery.findPeers((service) {
      if (mounted) {
        setState(() {
          if (service.name != null) {
            _discoveredNodeIds.add(service.name!);
            _activeNodesCount = _discoveredNodeIds.length;
            _networkHealth = _activeNodesCount > 0 ? 0.98 : 0.0;
            _shardsHostingCount = _activeNodesCount * 7;
          }
        });
      }
    });
  }

  @override
  void dispose() {
    _pulseController.dispose();
    _searchController.dispose();
    _peerDiscovery.stopNode();
    super.dispose();
  }

  Future<void> _refreshStream() async {
    setState(() => _isRanking = true);
    await Future.delayed(const Duration(milliseconds: 800));

    final data = _vaultBox.get('items', defaultValue: []);
    List<LearningInsight> rawList = (data as List).map((e) => LearningInsight.fromMap(e)).toList();
    
    List<LearningInsight> processedList = [];
    for (var insight in rawList) {
      double matchRate = insight.matchRate;
      if (insight.textContent != null) {
        double localScore = RankingEngine.calculateSimilarity(_currentSyllabus, insight.textContent!);
        // PURE VECTOR RANKING: Give the syllabus match 80% weight for higher accuracy
        matchRate = (insight.matchRate * 0.2) + (localScore * 0.8);
      }
      processedList.add(LearningInsight(
        id: insight.id, title: insight.title, provider: insight.provider,
        department: insight.department, matchRate: matchRate,
        isPrivate: insight.isPrivate, isProject: insight.isProject,
        textContent: insight.textContent, vaultPath: insight.vaultPath,
      ));
    }

    if (mounted) {
      setState(() {
        _insights = processedList;
        _insights.sort((a, b) => b.matchRate.compareTo(a.matchRate));
        if (_insights.isEmpty) {
          _insights = [
            LearningInsight(
              id: "1", title: "Information Retrieval Mastery", provider: "Dr. Kebede", department: "Computer Science", 
              matchRate: 0.98, textContent: "Information retrieval, vectors, search engines, indexing, tf-idf, cosine similarity."
            ),
            LearningInsight(
              id: "2", title: "Advanced Logic Systems", provider: "Prof. Sarah", department: "Engineering", 
              matchRate: 0.85, isPrivate: true, textContent: "Logic gates, boolean algebra, circuits, computer architecture, digital systems."
            ),
          ];
        }
        _isRanking = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLecturer = _currentStatus != UserStatus.foundation;
    final primaryColor = isLecturer ? Colors.deepPurple : Colors.indigo;
    final secondaryColor = isLecturer ? const Color(0xFFEDE9FE) : const Color(0xFFC7D2FE);

    return Scaffold(
      body: Stack(
        children: [
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  secondaryColor,
                  const Color(0xFFF8FAFC),
                  secondaryColor.withValues(alpha: 0.5),
                ],
              ),
            ),
          ),
          
          if (_isSidebarOpen)
            Positioned.fill(
              child: GestureDetector(
                onTap: () => setState(() => _isSidebarOpen = false),
                child: Container(color: Colors.black12),
              ),
            ),
          
          AnimatedContainer(
            duration: const Duration(milliseconds: 400),
            curve: Curves.easeInOutExpo,
            width: _isSidebarOpen ? 280 : 0,
            child: ClipRRect(
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                child: Container(
                  color: Colors.white.withValues(alpha: 0.7),
                  child: _buildSidebar(primaryColor),
                ),
              ),
            ),
          ),

          AnimatedContainer(
            duration: const Duration(milliseconds: 400),
            curve: Curves.easeInOutExpo,
            transform: Matrix4.translationValues(_isSidebarOpen ? 280 : 0, 0, 0),
            child: _buildDashboard(primaryColor),
          ),
        ],
      ),
    );
  }

  Widget _buildSidebar(Color primaryColor) {
    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(25),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Image.asset(
                'assets/images/app_logo.png',
                height: 80,
                errorBuilder: (context, error, stackTrace) => Icon(Icons.account_balance, size: 60, color: primaryColor),
              ),
            ),
            const SizedBox(height: 30),
            CircleAvatar(radius: 30, backgroundColor: primaryColor, child: const Icon(Icons.person, color: Colors.white)),
            const SizedBox(height: 20),
            const Text("Student Profile", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
            const Text("Department: CS", style: TextStyle(color: Colors.black54, fontSize: 12)),
            const Divider(height: 40),
            
            Text("DECENTRALIZED NETWORK", style: TextStyle(fontSize: 10, letterSpacing: 1.5, color: primaryColor, fontWeight: FontWeight.bold)),
            const SizedBox(height: 15),
            
            Container(
              height: 120,
              width: double.infinity,
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(15),
                border: Border.all(color: primaryColor.withValues(alpha: 0.1)),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(15),
                child: AnimatedBuilder(
                  animation: _pulseController,
                  builder: (context, child) => CustomPaint(
                    painter: NetworkHeatmapPainter(_activeNodesCount, _pulseController.value, primaryColor),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 15),
            _networkStat(Icons.hub_outlined, "Active Nodes", "$_activeNodesCount", primaryColor),
            _networkStat(Icons.grid_3x3, "Shards Hosting", "$_shardsHostingCount", primaryColor),
            _networkStat(Icons.security_update_good, "Network Health", "${(_networkHealth * 100).toInt()}%", primaryColor),
            
            const Divider(height: 40),
            _sidebarItem(Icons.bookmark_outline, "My Saved Items", primaryColor, () {
              setState(() {
                _showingSavedOnly = !_showingSavedOnly;
                _isSidebarOpen = false;
              });
            }, isActive: _showingSavedOnly),
            _sidebarItem(Icons.history, "Study History", primaryColor, () {
              showModalBottomSheet(
                context: context,
                backgroundColor: Colors.white,
                shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(30))),
                builder: (ctx) => Container(
                  padding: const EdgeInsets.all(30),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text("STUDY_HISTORY_LOG", style: TextStyle(fontWeight: FontWeight.w900, color: Colors.indigo)),
                      const SizedBox(height: 20),
                      if (_history.isEmpty) const Text("No recent activity.", style: TextStyle(fontSize: 12, color: Colors.black38)),
                      ..._history.map((h) => ListTile(title: Text(h, style: const TextStyle(fontSize: 12)), leading: const Icon(Icons.history, size: 16))),
                      const SizedBox(height: 20),
                    ],
                  ),
                ),
              );
            }),
            const Divider(height: 40),
            
            Text("ACCOUNT PROFILE", style: TextStyle(fontSize: 10, letterSpacing: 1.5, color: primaryColor, fontWeight: FontWeight.bold)),
            const SizedBox(height: 15),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: primaryColor.withValues(alpha: 0.05), borderRadius: BorderRadius.circular(15)),
              child: Row(
                children: [
                  Icon(_currentStatus == UserStatus.foundation ? Icons.school : Icons.workspace_premium, color: primaryColor),
                  const SizedBox(width: 15),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(_currentStatus == UserStatus.foundation ? "Student Access" : "Lecturer Access", 
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        Text(_currentStatus == UserStatus.foundation ? "View & Verify" : "Certify & Distribute", 
                          style: const TextStyle(fontSize: 10, color: Colors.black45)),
                      ],
                    ),
                  ),
                  Switch(
                    value: _currentStatus != UserStatus.foundation,
                    activeColor: primaryColor,
                    onChanged: (v) {
                      setState(() => _currentStatus = v ? UserStatus.contributor : UserStatus.foundation);
                      _refreshStream();
                    },
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 20),
            if (_currentStatus == UserStatus.contributor)
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => setState(() => _currentStatus = UserStatus.administrator),
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.deepPurple, foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                  child: const Text("Elevate to Admin Terminal", style: TextStyle(fontSize: 10)),
                ),
              ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _networkStat(IconData icon, String label, String value, Color primaryColor) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: Row(
      children: [
        Icon(icon, size: 16, color: Colors.black38),
        const SizedBox(width: 12),
        Text(label, style: const TextStyle(fontSize: 12, color: Colors.black54)),
        const Spacer(),
        Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: primaryColor)),
      ],
    ),
  );

  Widget _sidebarItem(IconData icon, String label, Color primaryColor, VoidCallback onTap, {bool isActive = false}) => InkWell(
    onTap: onTap,
    child: Padding(
      padding: const EdgeInsets.symmetric(vertical: 15),
      child: Row(children: [
        Icon(icon, size: 20, color: isActive ? Colors.green : primaryColor), 
        const SizedBox(width: 15), 
        Text(label, style: TextStyle(fontWeight: FontWeight.w500, color: isActive ? Colors.green : Colors.black87))
      ]),
    ),
  );

  Widget _buildDashboard(Color primaryColor) {
    final filteredInsights = _insights.where((i) {
      if (_showingSavedOnly && !_savedItemIds.contains(i.id)) return false;
      final matchesDept = _selectedDept == "All" || i.department == _selectedDept;
      final matchesSearch = i.title.toLowerCase().contains(_searchQuery.toLowerCase()) || 
                           i.provider.toLowerCase().contains(_searchQuery.toLowerCase());
      return matchesDept && matchesSearch;
    }).toList();

    return GestureDetector(
      onTap: () => setState(() => _isSidebarOpen = false),
      child: Container(
        decoration: BoxDecoration(
          color: _isSidebarOpen ? Colors.transparent : null,
          boxShadow: _isSidebarOpen ? [BoxShadow(color: Colors.black12, blurRadius: 40)] : null,
        ),
        child: SafeArea(
          bottom: false, // Fix potential overflow at bottom
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildAppBar(primaryColor),
              if (_isRanking) 
                LinearProgressIndicator(minHeight: 2, backgroundColor: Colors.transparent, color: primaryColor),
              Expanded(
                child: CustomScrollView(
                  slivers: [
                    SliverToBoxAdapter(child: _buildWelcomeSection()),
                    SliverToBoxAdapter(child: _buildSearchBar(primaryColor)),
                    SliverToBoxAdapter(child: _buildNetworkSummary(primaryColor)),
                    SliverToBoxAdapter(child: _buildLiveNetworkHeatmap(primaryColor)),
                    SliverToBoxAdapter(child: _buildDepartmentFilters(primaryColor)),
                    if (_showingSavedOnly)
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 10),
                          child: Row(
                            children: [
                              const Text("SAVED_RESULTS", style: TextStyle(fontWeight: FontWeight.w900, fontSize: 10, color: Colors.green)),
                              const Spacer(),
                              TextButton(onPressed: () => setState(() => _showingSavedOnly = false), child: const Text("Show All", style: TextStyle(fontSize: 10))),
                            ],
                          ),
                        ),
                      ),
                    SliverPadding(
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                      sliver: SliverList(
                        delegate: SliverChildBuilderDelegate(
                          (ctx, i) => _buildAnimatedInsightCard(filteredInsights[i], i, primaryColor),
                          childCount: filteredInsights.length,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSearchBar(Color primaryColor) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 5),
      child: Container(
        height: 45,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: primaryColor.withValues(alpha: 0.1)),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 10, offset: const Offset(0, 4))],
        ),
        child: TextField(
          controller: _searchController,
          onChanged: (v) => setState(() => _searchQuery = v),
          maxLines: 1,
          style: const TextStyle(fontSize: 13),
          textAlignVertical: TextAlignVertical.center,
          decoration: InputDecoration(
            hintText: "Search knowledge stream...",
            hintStyle: const TextStyle(fontSize: 13, color: Colors.black26),
            prefixIcon: Icon(Icons.search, color: primaryColor, size: 18),
            isDense: true,
            border: InputBorder.none,
            contentPadding: const EdgeInsets.symmetric(horizontal: 15),
          ),
        ),
      ),
    );
  }

  Widget _buildLiveNetworkHeatmap(Color primaryColor) {
    return Container(
      height: 100,
      margin: const EdgeInsets.symmetric(horizontal: 25, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(25),
        border: Border.all(color: Colors.white),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withValues(alpha: 0.1),
            blurRadius: 20,
            offset: const Offset(0, 10),
          )
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(25),
        child: Stack(
          children: [
            AnimatedBuilder(
              animation: _pulseController,
              builder: (context, child) => CustomPaint(
                size: const Size(double.infinity, 100),
                painter: NetworkHeatmapPainter(_activeNodesCount, _pulseController.value, primaryColor),
              ),
            ),
            Positioned(
              left: 20, top: 15,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text("LIVE_NETWORK_TOPOLOGY", style: TextStyle(fontSize: 8, fontWeight: FontWeight.w900, color: primaryColor, letterSpacing: 1)),
                  Text("$_activeNodesCount NODES DISCOVERED", style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black87)),
                ],
              ),
            ),
            Positioned(
              right: 20, bottom: 15,
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.circle, size: 8, color: Colors.green),
                  const SizedBox(width: 5),
                  Text("STREAM_ACTIVE", style: TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: Colors.green)),
                ],
              ),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildNetworkSummary(Color primaryColor) {
    return Container(
      height: 70,
      margin: const EdgeInsets.symmetric(vertical: 10),
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 25),
        children: [
          _statBox("ACTIVE_PEERS", "$_activeNodesCount", Icons.hub, primaryColor),
          _statBox("SHARDS_SYNCED", "$_shardsHostingCount", Icons.cloud_done, primaryColor),
          _statBox("NETWORK_LOAD", "2.4 MB/s", Icons.speed, primaryColor),
          _statBox("V_HEALTH", "${(_networkHealth * 100).toInt()}%", Icons.verified_user, primaryColor),
        ],
      ),
    );
  }

  Widget _statBox(String label, String value, IconData icon, Color primaryColor) {
    return Container(
      width: 130,
      margin: const EdgeInsets.only(right: 15, bottom: 5),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withValues(alpha: 0.08),
            blurRadius: 12,
            offset: const Offset(0, 4),
          )
        ],
      ),
      child: Row(
        children: [
          Icon(icon, size: 14, color: primaryColor.withValues(alpha: 0.5)),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  label,
                  style: const TextStyle(fontSize: 7, color: Colors.black38, fontWeight: FontWeight.w900),
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  value,
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: primaryColor),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _buildDepartmentFilters(Color primaryColor) {
    return Container(
      height: 40,
      margin: const EdgeInsets.symmetric(vertical: 15),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 25),
        itemCount: _departments.length,
        itemBuilder: (ctx, i) {
          bool isSelected = _selectedDept == _departments[i];
          return GestureDetector(
            onTap: () => setState(() => _selectedDept = _departments[i]),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              margin: const EdgeInsets.only(right: 12),
              padding: const EdgeInsets.symmetric(horizontal: 20),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: isSelected ? primaryColor : Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: isSelected ? [BoxShadow(color: primaryColor.withValues(alpha: 0.3), blurRadius: 10, offset: const Offset(0, 5))] : null,
                border: isSelected ? null : Border.all(color: Colors.black.withValues(alpha: 0.05)),
              ),
              child: Text(
                _departments[i],
                style: TextStyle(
                  color: isSelected ? Colors.white : Colors.black54,
                  fontSize: 11,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildAppBar(Color primaryColor) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      child: Row(
        children: [
          IconButton(
            icon: Icon(_isSidebarOpen ? Icons.close : Icons.menu_open, color: primaryColor),
            onPressed: () => setState(() => _isSidebarOpen = !_isSidebarOpen),
          ),
          Expanded(
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Flexible(
                  child: Image.asset(
                    'assets/images/app_logo.png',
                    height: 24,
                    errorBuilder: (context, error, stackTrace) => const SizedBox.shrink(),
                  ),
                ),
                const SizedBox(width: 8),
                const Flexible(
                  child: Text(
                    "ACADEMIA VAULT",
                    style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 2, fontSize: 14),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (_currentStatus == UserStatus.administrator)
                IconButton(
                  icon: const Icon(Icons.admin_panel_settings_outlined, color: Colors.deepPurple, size: 20),
                  onPressed: _showAdminTerminal,
                ),
              if (_currentStatus == UserStatus.contributor || _currentStatus == UserStatus.administrator)
                IconButton(
                  icon: Icon(Icons.add_circle_outline, color: primaryColor, size: 24),
                  onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (c) => const UploadPortal())),
                )
              else
                const SizedBox(width: 40),
            ],
          ),
        ],
      ),
    );
  }

  void _showAdminTerminal() {
    final ctrl = TextEditingController(text: _currentSyllabus);
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(30))),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom, left: 30, right: 30, top: 30),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("ADMIN_SYLLABUS_CONTROL", style: TextStyle(fontWeight: FontWeight.w900, fontSize: 12, color: Colors.deepPurple, letterSpacing: 1)),
            const SizedBox(height: 20),
            const Text("Update the Global Syllabus to trigger a network-wide re-ranking of all academic materials based on new learning objectives.", style: TextStyle(fontSize: 11, color: Colors.black54)),
            const SizedBox(height: 25),
            TextField(
              controller: ctrl,
              maxLines: 4,
              style: const TextStyle(fontSize: 13, height: 1.5),
              decoration: InputDecoration(
                filled: true, fillColor: Colors.black.withValues(alpha: 0.02),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: const BorderSide(color: Colors.black12)),
              ),
            ),
            const SizedBox(height: 30),
            SizedBox(
              width: double.infinity,
              height: 55,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: Colors.deepPurple, foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15))),
                onPressed: () {
                  setState(() => _currentSyllabus = ctrl.text);
                  _refreshStream(); 
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Global Syllabus Updated. Materials Re-ranked.")));
                },
                child: const Text("BROADCAST SYLLABUS UPDATE", style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildWelcomeSection() {
    final hour = DateTime.now().hour;
    String greeting = "Good Morning";
    if (hour >= 12 && hour < 17) greeting = "Good Afternoon";
    if (hour >= 17) greeting = "Good Evening";

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 15),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text("$greeting, Scholar.", 
                    style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: Colors.black87, letterSpacing: -0.5)),
                  const Text("The highest study matches for your department:", 
                    style: TextStyle(color: Colors.black45, fontSize: 12, fontWeight: FontWeight.w500)),
                ],
              ),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10)]
                ),
                child: const Icon(Icons.auto_awesome, color: Colors.amber, size: 20),
              )
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAnimatedInsightCard(LearningInsight insight, int index, Color primaryColor) {
    return TweenAnimationBuilder(
      duration: Duration(milliseconds: 600 + (index * 100)),
      tween: Tween<double>(begin: 0, end: 1),
      builder: (ctx, double value, child) {
        return Opacity(
          opacity: value,
          child: Transform.translate(
            offset: Offset(0, 50 * (1 - value)),
            child: child,
          ),
        );
      },
      child: InkWell(
        onTap: () => _decryptAndOpen(insight, primaryColor),
        child: Container(
          margin: const EdgeInsets.only(bottom: 20),
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.9),
            borderRadius: BorderRadius.circular(30),
            border: Border.all(color: Colors.white),
            boxShadow: [
              BoxShadow(
                color: primaryColor.withValues(alpha: 0.12),
                blurRadius: 25,
                offset: const Offset(0, 15),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Flexible(child: _badge(insight.department, primaryColor.withValues(alpha: 0.1), primaryColor)),
                  Flexible(
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          icon: Icon(_savedItemIds.contains(insight.id) ? Icons.bookmark : Icons.bookmark_border, size: 16, color: primaryColor),
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                          onPressed: () => setState(() {
                            if (_savedItemIds.contains(insight.id)) {
                              _savedItemIds.remove(insight.id);
                            } else {
                              _savedItemIds.add(insight.id);
                            }
                          }),
                        ),
                        const SizedBox(width: 4),
                        const Icon(Icons.analytics_outlined, size: 10, color: Colors.green),
                        const SizedBox(width: 4),
                        Flexible(
                          child: Text(
                            "${(insight.matchRate * 100).toInt()}% Match",
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: Colors.green),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(insight.title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              const SizedBox(height: 4),
              Text("Provided by ${insight.provider}", style: const TextStyle(color: Colors.black38, fontSize: 11)),
              const Divider(height: 30),
              Row(
                children: [
                  if (insight.isPrivate) const Icon(Icons.lock_outline, size: 14, color: Colors.orange),
                  if (insight.isPrivate) const SizedBox(width: 5),
                  Text(insight.isPrivate ? "Secure Access Only" : "Public Material", style: TextStyle(color: insight.isPrivate ? Colors.orange : Colors.blue, fontSize: 10, fontWeight: FontWeight.bold)),
                  const Spacer(),
                  Icon(Icons.arrow_right_alt, color: primaryColor),
                ],
              )
            ],
          ),
        ),
      ),
    );
  }

  Widget _badge(String text, Color bg, Color textCol) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
    decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(10)),
    child: Text(text, style: TextStyle(color: textCol, fontSize: 9, fontWeight: FontWeight.bold)),
  );

  Future<void> _decryptAndOpen(LearningInsight insight, Color primaryColor) async {
    if (insight.id == "1" || insight.id == "2") {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text("DEMO_MODE: This is a placeholder. Material extraction requires a real uploaded file."),
        backgroundColor: primaryColor,
      ));
      return;
    }

    if (insight.isPrivate) {
      try {
        final bool didAuth = await _auth.authenticate(
          localizedReason: 'AUTHORIZE ACCESS TO ENCRYPTED KNOWLEDGE',
          options: const AuthenticationOptions(biometricOnly: true, stickyAuth: true),
        );
        if (!didAuth) return;
      } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("BIOMETRIC_FAILURE: $e")));
        return;
      }
    }

    if (!mounted) return;
    showGeneralDialog(
      context: context,
      barrierDismissible: false,
      barrierColor: Colors.black.withValues(alpha: 0.95),
      transitionDuration: const Duration(milliseconds: 300),
      pageBuilder: (ctx, anim1, anim2) {
        return Scaffold(
          backgroundColor: Colors.transparent,
          body: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.security, color: primaryColor, size: 50),
                const SizedBox(height: 30),
                const Text("VAULT PROTOCOL: PIXEL_DECONSTRUCTION", style: TextStyle(color: Colors.white24, fontSize: 10, letterSpacing: 2, fontWeight: FontWeight.bold)),
                const SizedBox(height: 20),
                SizedBox(width: 150, child: LinearProgressIndicator(backgroundColor: Colors.white10, color: primaryColor, minHeight: 1)),
                const SizedBox(height: 20),
                Text("SEARCHING IMAGE BYTES FOR SHARDS...", style: TextStyle(color: primaryColor, fontSize: 8, fontWeight: FontWeight.w900)),
              ],
            ),
          ),
        );
      },
    );

    try {
      await Future.delayed(const Duration(seconds: 2));
      setState(() => _history.insert(0, "Opened: ${insight.title} (${DateTime.now().hour}:${DateTime.now().minute})"));

      final List items = _vaultBox.get('items', defaultValue: []);
      final rawMatch = items.firstWhere((e) => e['id'] == insight.id, orElse: () => null);
      if (rawMatch == null || rawMatch['vaultPath'] == null) throw Exception("SHARD_SOURCE_NOT_FOUND");
      final file = File(rawMatch['vaultPath']);
      
      if (insight.isPrivate) {
        final Uint8List? bytes = await SteganoEngine.extractKnowledge(file);
        if (bytes != null) {
          final tempDir = await getTemporaryDirectory();
          final tempFile = File('${tempDir.path}/xtr_${insight.id}.pdf');
          await tempFile.writeAsBytes(bytes);
          if (mounted) Navigator.pop(context);
          await OpenFilex.open(tempFile.path);
        } else {
          throw Exception("CHECKSUM_MISMATCH");
        }
      } else {
        if (mounted) Navigator.pop(context);
        await OpenFilex.open(file.path);
      }
    } catch (e) {
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("VAULT_ERROR: $e"), backgroundColor: Colors.redAccent));
      }
    }
  }
}

class NetworkHeatmapPainter extends CustomPainter {
  final int nodeCount;
  final double animationValue;
  final Color themeColor;
  NetworkHeatmapPainter(this.nodeCount, this.animationValue, this.themeColor);

  @override
  void paint(Canvas canvas, Size size) {
    final random = math.Random(42);
    final nodes = List.generate(nodeCount + 5, (index) => Offset(random.nextDouble() * size.width, random.nextDouble() * size.height));
    final paint = Paint()..color = themeColor.withValues(alpha: 0.2)..strokeWidth = 1.0;

    for (int i = 0; i < nodes.length; i++) {
      for (int j = i + 1; j < nodes.length; j++) {
        final dist = (nodes[i] - nodes[j]).distance;
        if (dist < 60) canvas.drawLine(nodes[i], nodes[j], paint..color = themeColor.withValues(alpha: (1 - dist / 60) * 0.2));
      }
    }

    for (var node in nodes) {
      final pulse = math.sin(animationValue * math.pi) * 4;
      canvas.drawCircle(node, 3 + pulse, Paint()..color = themeColor.withValues(alpha: 0.6));
      canvas.drawCircle(node, (3 + pulse) * 2, Paint()..color = themeColor.withValues(alpha: 0.1));
    }
  }

  @override
  bool shouldRepaint(CustomPainter oldDelegate) => true;
}
