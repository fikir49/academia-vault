import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

class ApiService {
  // Configurable base URL for local backend or node IP
  static String baseUrl = 'http://10.0.2.2:8000/api'; // Standard Android Emulator localhost; update for physical device IP

  static const Duration timeoutDuration = Duration(seconds: 10);

  // Common Headers
  static Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

  /// Fetch top learning insights from backend / peer node
  static Future<List<Map<String, dynamic>>> fetchInsights({String department = 'All'}) async {
    final uri = Uri.parse('$baseUrl/insights?department=${Uri.encodeComponent(department)}');

    try {
      final response = await http.get(uri, headers: _headers).timeout(timeoutDuration);

      if (response.statusCode == 200) {
        final List<dynamic> data = jsonDecode(response.body);
        return data.cast<Map<String, dynamic>>();
      } else {
        throw HttpException('Failed to load insights. Status: ${response.statusCode}');
      }
    } catch (e) {
      // Fallback on network failure
      return [];
    }
  }

  /// Upload new academic material metadata and vector payload
  static Future<bool> uploadInsight(Map<String, dynamic> insightData) async {
    final uri = Uri.parse('$baseUrl/insights/upload');

    try {
      final response = await http
          .post(
            uri,
            headers: _headers,
            body: jsonEncode(insightData),
          )
          .timeout(timeoutDuration);

      return response.statusCode == 200 || response.statusCode == 201;
    } catch (e) {
      return false;
    }
  }

  /// Broadcast syllabus update across network nodes for re-ranking
  static Future<bool> updateGlobalSyllabus(String syllabusText) async {
    final uri = Uri.parse('$baseUrl/syllabus/update');

    try {
      final response = await http
          .post(
            uri,
            headers: _headers,
            body: jsonEncode({'syllabus': syllabusText}),
          )
          .timeout(timeoutDuration);

      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  /// Sync local node shard catalog with discovered P2P peer address
  static Future<List<Map<String, dynamic>>> syncWithPeerNode(String peerIp, int port) async {
    final uri = Uri.parse('http://$peerIp:$port/api/peer/catalog');

    try {
      final response = await http.get(uri, headers: _headers).timeout(timeoutDuration);

      if (response.statusCode == 200) {
        final List<dynamic> catalog = jsonDecode(response.body);
        return catalog.cast<Map<String, dynamic>>();
      }
    } catch (_) {}
    return [];
  }
}