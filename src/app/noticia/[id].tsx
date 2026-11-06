import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { Stack, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { useState } from 'react';
import { ScrollView, Share, StyleSheet, Text, View } from 'react-native';

import { chamar } from '../../api/cliente';
import { Botao, Carregando, Falha, useCarregar } from '../../componentes/ui';
import { cores, fonte } from '../../tema';

type Bloco = { t: 'p'; x: string } | { t: 'sub'; x: string } | { t: 'img'; src: string };

interface Noticia {
  id: number;
  titulo: string;
  imagem: string | null;
  fonte: string;
  link: string;
  data: string;
  /** true: texto completo reproduzido; false: só o resumo (a fonte não permite reproduzir). */
  completa: boolean;
  blocos: Bloco[];
}

// Notícia montada dentro do app, sempre com a fonte e o link da matéria original
export default function TelaNoticia() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { dados: n, erro, carregando, recarregar } = useCarregar(
    () => chamar<{ noticia: Noticia }>(`noticia.php?id=${id}`).then((r) => r.noticia),
    [id],
  );
  if (carregando && !n) return <Carregando />;
  if (erro && !n) return <Falha mensagem={erro} tentar={recarregar} />;
  if (!n) return null;

  const abrirOriginal = () => WebBrowser.openBrowserAsync(n.link);

  return (
    <>
      <Stack.Screen options={{ title: 'Notícia' }} />
      <ScrollView contentContainerStyle={{ paddingBottom: 48 }}>
        {n.imagem && <ImagemAuto src={n.imagem} />}
        <View style={{ padding: 16, gap: 14 }}>
          <Text style={estilos.titulo}>{n.titulo}</Text>
          <View style={estilos.fonteTopo}>
            <MaterialCommunityIcons name="newspaper-variant-outline" size={20} color={cores.textoSuave} />
            <Text style={estilos.meta}>
              {n.fonte} · {n.data}
            </Text>
          </View>

          {n.blocos.map((b, i) =>
            b.t === 'img' ? (
              <ImagemAuto key={i} src={b.src} arredondada />
            ) : b.t === 'sub' ? (
              <Text key={i} style={estilos.sub}>
                {b.x}
              </Text>
            ) : (
              <Text key={i} style={estilos.paragrafo}>
                {b.x}
              </Text>
            ),
          )}

          {!n.completa && (
            <Text style={[estilos.paragrafo, { color: cores.textoSuave }]}>
              {n.blocos.length ? 'Este é um resumo. ' : ''}A matéria completa está no site {n.fonte}.
            </Text>
          )}

          <View style={estilos.referencia}>
            <Text style={estilos.refTitulo}>Fonte</Text>
            <Text style={estilos.refTexto}>{n.fonte}</Text>
            <Text style={estilos.refLink} onPress={abrirOriginal} accessibilityRole="link">
              {n.link}
            </Text>
            <Botao
              titulo={n.completa ? 'Ver no site original' : 'Ler a matéria completa'}
              icone="open-in-new"
              tipo="contorno"
              onPress={abrirOriginal}
              estilo={{ marginTop: 12 }}
            />
          </View>
          <Botao
            titulo="Compartilhar"
            icone="share-variant-outline"
            tipo="contorno"
            onPress={() => Share.share({ message: `${n.titulo}\n${n.link}\n\nVi no Guia Sanjoanense` })}
          />
        </View>
      </ScrollView>
    </>
  );
}

/** Imagem na largura da tela, com a altura proporcional à foto. */
function ImagemAuto({ src, arredondada }: { src: string; arredondada?: boolean }) {
  const [proporcao, setProporcao] = useState(16 / 9);
  return (
    <Image
      source={src}
      contentFit="cover"
      transition={150}
      onLoad={(e) => {
        if (e.source.width && e.source.height) setProporcao(e.source.width / e.source.height);
      }}
      style={{ width: '100%', aspectRatio: proporcao, backgroundColor: cores.fundoSuave, borderRadius: arredondada ? 12 : 0 }}
      accessible={false}
    />
  );
}

const estilos = StyleSheet.create({
  titulo: { fontSize: fonte.titulo, fontWeight: '800', color: cores.texto, lineHeight: 36 },
  fonteTopo: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  meta: { fontSize: fonte.pequena, color: cores.textoSuave, flex: 1 },
  sub: { fontSize: fonte.grande - 2, fontWeight: '800', color: cores.texto, marginTop: 8 },
  paragrafo: { fontSize: fonte.media, color: cores.texto, lineHeight: 31 },
  referencia: { backgroundColor: cores.fundoSuave, borderRadius: 16, padding: 16, marginTop: 12, borderLeftWidth: 4, borderLeftColor: cores.magenta },
  refTitulo: { fontSize: fonte.pequena, fontWeight: '800', color: cores.textoSuave, textTransform: 'uppercase', letterSpacing: 1 },
  refTexto: { fontSize: fonte.normal, fontWeight: '700', color: cores.texto, marginTop: 4 },
  refLink: { fontSize: fonte.pequena, color: cores.ciano, textDecorationLine: 'underline', marginTop: 4 },
});
