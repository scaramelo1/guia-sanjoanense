import { useEffect, useState } from 'react';
import { FlatList, TextInput, View } from 'react-native';

import { chamar, type ProfissionalResumo } from '../api/cliente';
import { CartaoProfissional, Carregando, Vazio, estilos } from '../componentes/ui';
import { cores } from '../tema';

export default function TelaBusca() {
  const [termo, setTermo] = useState('');
  const [itens, setItens] = useState<ProfissionalResumo[] | null>(null);
  const [erro, setErro] = useState<string | null>(null);
  const [buscando, setBuscando] = useState(false);

  // busca enquanto digita, esperando a pessoa parar por meio segundo
  useEffect(() => {
    const t = termo.trim();
    if (t.length < 2) {
      setItens(null);
      setErro(null);
      return;
    }
    let cancelado = false;
    const espera = setTimeout(async () => {
      setBuscando(true);
      try {
        const r = await chamar<{ profissionais: ProfissionalResumo[] }>(`busca.php?q=${encodeURIComponent(t)}`);
        if (!cancelado) {
          setItens(r.profissionais);
          setErro(null);
        }
      } catch (e) {
        if (!cancelado) setErro(e instanceof Error ? e.message : 'Não foi possível buscar.');
      } finally {
        if (!cancelado) setBuscando(false);
      }
    }, 500);
    return () => {
      cancelado = true;
      clearTimeout(espera);
    };
  }, [termo]);

  return (
    <View style={{ flex: 1 }}>
      <View style={{ padding: 16, paddingBottom: 8 }}>
        <TextInput
          autoFocus
          value={termo}
          onChangeText={setTermo}
          placeholder="Ex.: chuveiro, bolo, faxina"
          placeholderTextColor={cores.textoSuave}
          returnKeyType="search"
          style={estilos.campo}
          accessibilityLabel="Digite o que você precisa"
        />
      </View>
      {buscando && !itens ? (
        <Carregando />
      ) : erro ? (
        <Vazio icone="wifi-off" texto={erro} />
      ) : itens === null ? (
        <Vazio icone="magnify" texto="Digite o serviço, o nome do profissional ou o bairro." />
      ) : (
        <FlatList
          data={itens}
          keyExtractor={(p) => String(p.id)}
          contentContainerStyle={{ padding: 16, flexGrow: 1 }}
          keyboardShouldPersistTaps="handled"
          renderItem={({ item }) => <CartaoProfissional p={item} />}
          ListEmptyComponent={<Vazio icone="account-search-outline" texto="Nada encontrado. Tente outra palavra." />}
        />
      )}
    </View>
  );
}
