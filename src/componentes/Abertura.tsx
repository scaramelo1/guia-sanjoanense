import * as SplashScreen from 'expo-splash-screen';
import { useEffect, useRef, useState } from 'react';
import { Animated, Easing, StyleSheet, Text, View } from 'react-native';

import { cores, fonte } from '../tema';

// Segura a splash nativa (logo de 200 px no fundo branco, definida no app.json) até a abertura animada
// estar desenhada. Como a abertura começa idêntica, a troca não aparece.
SplashScreen.preventAutoHideAsync().catch(() => {});

const LOGO = 200;
const DURACAO_PARADA = 1400; // tempo com o nome na tela antes de sumir

/**
 * Splash Screen do Guia Sanjoanense: o "S" da página Sanjoanenses sobe um pouco, aparecem o nome,
 * a faixa ciano/magenta e "São João da Boa Vista"; depois tudo some e mostra o app.
 * Tocar na tela pula a abertura.
 */
export function Abertura({ children }: { children: React.ReactNode }) {
  const [visivel, setVisivel] = useState(true);
  const logo = useRef(new Animated.Value(0)).current; // 0 = igual à splash nativa, 1 = posição final
  const texto = useRef(new Animated.Value(0)).current;
  const faixa = useRef(new Animated.Value(0)).current;
  const saida = useRef(new Animated.Value(1)).current;
  const iniciou = useRef(false);

  function sair() {
    Animated.timing(saida, { toValue: 0, duration: 350, useNativeDriver: true }).start(() => setVisivel(false));
  }

  function iniciar() {
    if (iniciou.current) return;
    iniciou.current = true;
    SplashScreen.hideAsync().catch(() => {});
    Animated.sequence([
      Animated.timing(logo, { toValue: 1, duration: 550, easing: Easing.out(Easing.back(1.4)), useNativeDriver: true }),
      Animated.parallel([
        Animated.timing(texto, { toValue: 1, duration: 450, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
        Animated.timing(faixa, { toValue: 1, duration: 600, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
      ]),
      Animated.delay(DURACAO_PARADA),
    ]).start(({ finished }) => finished && sair());
  }

  // Garantia: se algo travar a animação, o app aparece mesmo assim.
  useEffect(() => {
    const t = setTimeout(() => setVisivel(false), 6000);
    return () => clearTimeout(t);
  }, []);

  return (
    <View style={{ flex: 1 }}>
      {children}
      {visivel && (
        <Animated.View
          style={[StyleSheet.absoluteFill, estilos.fundo, { opacity: saida }]}
          onLayout={iniciar}
          onTouchEnd={sair}
          accessibilityLabel="Guia Sanjoanense, São João da Boa Vista"
        >
          <Animated.Image
            source={require('../../assets/splash-icon.png')}
            style={[
              estilos.logo,
              {
                transform: [
                  { translateY: logo.interpolate({ inputRange: [0, 1], outputRange: [0, -70] }) },
                  { scale: logo.interpolate({ inputRange: [0, 1], outputRange: [1, 0.8] }) },
                ],
              },
            ]}
          />
          <Animated.View
            style={[
              estilos.textos,
              {
                opacity: texto,
                transform: [{ translateY: texto.interpolate({ inputRange: [0, 1], outputRange: [20, 0] }) }],
              },
            ]}
          >
            <Text style={estilos.titulo}>Guia Sanjoanense</Text>
            <Animated.View style={[estilos.faixa, { transform: [{ scaleX: faixa }] }]}>
              <View style={[estilos.faixaParte, { backgroundColor: cores.ciano }]} />
              <View style={[estilos.faixaParte, { backgroundColor: cores.magenta }]} />
            </Animated.View>
            <Text style={estilos.cidade}>São João da Boa Vista</Text>
          </Animated.View>
          <Animated.Text style={[estilos.rodape, { opacity: texto }]}>da página Sanjoanenses</Animated.Text>
        </Animated.View>
      )}
    </View>
  );
}

const estilos = StyleSheet.create({
  fundo: { backgroundColor: cores.fundo, alignItems: 'center', justifyContent: 'center', zIndex: 100 },
  logo: { width: LOGO, height: LOGO },
  textos: { position: 'absolute', top: '50%', marginTop: 70, alignItems: 'center' },
  titulo: { fontSize: 34, fontWeight: '900', color: cores.texto, letterSpacing: 0.5 },
  faixa: { flexDirection: 'row', width: 140, height: 6, borderRadius: 3, overflow: 'hidden', marginVertical: 12 },
  faixaParte: { flex: 1 },
  cidade: { fontSize: fonte.media, color: cores.textoSuave, fontWeight: '600' },
  rodape: { position: 'absolute', bottom: 48, fontSize: fonte.pequena, color: cores.textoSuave },
});
