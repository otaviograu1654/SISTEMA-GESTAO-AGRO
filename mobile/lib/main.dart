// teste da função main
import 'package:flutter/material.dart';

void main() {
  runApp(const PecuariaApp());
}

class PecuariaApp extends StatelessWidget {
  const PecuariaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'SGA Pecuária',
      home: Scaffold(
        appBar: AppBar(
          title: const Text('SGA Pecuária'),
        ),
        body: const Center(
          child: Text('App iniciado com sucesso'),
        ),
      ),
    );
  }
}
