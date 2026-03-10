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
      title: 'SGA Pecuária',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.green,
      ),
      home: const HomePage(),
    );
  }
}

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  final List<Map<String, String>> animais = const [
    {
      'brinco': '4052',
      'nome': 'Campeão',
      'raca': 'Nelore',
      'status': 'Pendente sync',
    },
    {
      'brinco': '4053',
      'nome': 'Estrela',
      'raca': 'Angus',
      'status': 'Sincronizado',
    },
    {
      'brinco': '4054',
      'nome': 'Trovão',
      'raca': 'Girolando',
      'status': 'Sincronizado',
    },
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('SGA Pecuária'),
        centerTitle: true,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.green.shade100,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Resumo do Rebanho',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 8),
                  Text('Total de animais: 3'),
                  Text('Registros pendentes: 1'),
                  Text('Modo offline: ativo'),
                ],
              ),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: ListView.builder(
                itemCount: animais.length,
                itemBuilder: (context, index) {
                  final animal = animais[index];

                  return Card(
                    child: ListTile(
                      leading: const CircleAvatar(
                        child: Icon(Icons.pets),
                      ),
                      title: Text(animal['nome'] ?? ''),
                      subtitle: Text(
                        'Brinco: ${animal['brinco']} | Raça: ${animal['raca']}',
                      ),
                      trailing: Text(
                        animal['status'] ?? '',
                        style: TextStyle(
                          color: animal['status'] == 'Sincronizado'
                              ? Colors.green
                              : Colors.orange,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text(
                        'Aqui depois entra a tela de novo registro',
                      ),
                    ),
                  );
                },
                icon: const Icon(Icons.add),
                label: const Text('Novo registro'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
