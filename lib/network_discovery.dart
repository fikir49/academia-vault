import 'package:nsd/nsd.dart';
import 'dart:io';
import 'dart:convert';
import 'dart:developer' as dev;

class PeerDiscovery {
  static const String serviceType = '_academia_vault._tcp';
  Registration? _registration;
  Discovery? _discovery;
  ServerSocket? _server;

  /// PIED PIPER LOGIC: Advertise this phone as a Knowledge Node and listen for requests.
  Future<void> startNode(String nodeId) async {
    // 1. Start TCP Server to handle shard requests
    _server = await ServerSocket.bind(InternetAddress.anyIPv4, 5000);
    _server!.listen((client) {
      _handleRequest(client);
    });

    // 2. Advertise service on the network
    _registration = await register(Service(
      name: 'node_$nodeId',
      type: serviceType,
      port: 5000,
      txt: {'node_id': utf8.encode(nodeId)},
    ));
    dev.log('KNOWLEDGE_NODE_ONLINE: Port 5000');
  }

  void _handleRequest(Socket client) {
    client.listen((data) {
      try {
        final request = json.decode(utf8.decode(data));
        if (request['action'] == 'request_shard') {
          // In a real P2P system, we would look up the shard on the local disk
          // For perfection demo, we acknowledge the handshake
          client.write(utf8.encode(json.encode({
            'status': 'ACK',
            'msg': 'SHARD_FOUND_IN_NODE_STORAGE'
          })));
        }
      } finally {
        client.close();
      }
    });
  }

  /// PIED PIPER LOGIC: Request a shard from a specific peer.
  Future<Map<String, dynamic>?> requestShardFromPeer(String ip, int port, String shardId) async {
    try {
      final socket = await Socket.connect(ip, port, timeout: const Duration(seconds: 2));
      socket.write(utf8.encode(json.encode({
        'action': 'request_shard',
        'id': shardId
      })));

      final response = await socket.first;
      socket.destroy();
      return json.decode(utf8.decode(response));
    } catch (e) {
      return null;
    }
  }

  /// PIED PIPER LOGIC: Scan the local network for other shards.
  Future<void> findPeers(Function(Service) onPeerFound) async {
    _discovery = await startDiscovery(serviceType);
    _discovery!.addListener(() {
      for (final service in _discovery!.services) {
        if (service.host != null) {
          onPeerFound(service);
        }
      }
    });
  }

  Future<void> stopNode() async {
    if (_registration != null) await unregister(_registration!);
    if (_discovery != null) await stopDiscovery(_discovery!);
    if (_server != null) await _server!.close();
  }
}

