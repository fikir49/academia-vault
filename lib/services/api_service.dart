import 'package:http/http.dart' as http;
import 'dart:convert';

class ApiService {
  // REPLACE THIS with your specific GitHub URL from the browser!
 // Use the 8000 URL here, because that's where Laravel lives!
static const String baseUrl = 'https://verbose-dollop-v694jjpp6qp2x659-8000.app.github.dev/api/v1';
  Future<void> checkServerStatus() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/status'));

      if (response.statusCode == 200) {
        var data = jsonDecode(response.body);
        print("Connected to Backend! Server Time: ${data['server_time']}");
      } else {
        print("Server error: ${response.statusCode}");
      }
    } catch (e) {
      print("Could not connect to server: $e");
    }
  }
}