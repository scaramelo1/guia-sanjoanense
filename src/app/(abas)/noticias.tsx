import { Image } from 'expo-image';
import * as WebBrowser from 'expo-web-browser';
import { FlatList, Pressable, StyleSheet, Text, View } from 'react-native';

import { chamar } from '../../api/cliente';
import { Carregando, Falha, Vazio, useCarregar } from '../../componentes/ui';
import { cores, fonte } from '../../tema';

interface Noticia {
  id: number;
  titulo: string;
  link: string;
  imagem: string | null;
  fonte: string;
  quando: string;
}

// Manchetes de São João coletadas pelo servidor. Tocar abre a matéria no site de origem.
export default function TelaNoticias() {
  const { dados, erro, carregando, recarregar } = useCarregar(
    () => chamar<{ noticias: Noticia[] }>('noticias.php').then((r) => r.noticias),
    [],
  );
  if (carregando && !dados) return <Carregando />;
  if (erro && !dados) return <Falha mensagem={erro} tentar={recarregar} />;
  return (
    <FlatList
      data={dados ?? []}
      keyExtractor={(n) => String(n.id)}
      contentContainerStyle={{ padding: 16, flexGrow: 1 }}
      refreshing={carregando}
      onRefresh={recarregar}
      ListHeaderComponent={<Text style={estilos.aviso}>O que está acontecendo em São João da Boa Vista</Text>}
      ListEmptyComponent={<Vazio icone="newspaper-variant-outline" texto="Nenhuma notícia por enquanto. Puxe para baixo para atualizar." />}
      renderItem={({ item: n }) => (
        <Pressable
          accessibilityRole="link"
          accessibilityLabel={`${n.titulo}. ${n.fonte}, ${n.quando}`}
          onPress={() => WebBrowser.openBrowserAsync(n.link)}
          style={({ pressed }) => [estilos.cartao, pressed && { backgroundColor: cores.fundoSuave }]}
        >
          {n.imagem && <Image source={n.imagem} style={estilos.imagem} contentFit="cover" transition={150} />}
          <View style={{ padding: 14, gap: 6 }}>
            <Text style={estilos.titulo}>{n.titulo}</Text>
            <Text style={estilos.meta}>
              {n.fonte} · {n.quando}
            </Text>
          </View>
        </Pressable>
      )}
    />
  );
}

const estilos = StyleSheet.create({
  aviso: { fontSize: fonte.normal, color: cores.textoSuave, marginBottom: 12 },
  cartao: { borderWidth: 1, borderColor: cores.borda, borderRadius: 16, overflow: 'hidden', marginBottom: 14, backgroundColor: cores.fundo },
  imagem: { width: '100%', aspectRatio: 16 / 9, backgroundColor: cores.fundoSuave },
  titulo: { fontSize: fonte.media, fontWeight: '700', color: cores.texto, lineHeight: 27 },
  meta: { fontSize: fonte.pequena, color: cores.textoSuave },
});
