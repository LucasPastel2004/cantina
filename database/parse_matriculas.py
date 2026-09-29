import re
import json

def parse_pdf_text():
    with open('storage/matriculas_raw.txt', 'r', encoding='utf-8') as f:
        text = f.read()

    pages = text.split('=== PÁGINA ')
    lines = []
    for p in pages[1:]:
        p_lines = p.split('\n')
        for l in p_lines:
            s = l.strip()
            if not s:
                continue
            if 'COLÉGIO MONTEIRO LOBATO' in s:
                continue
            if 'Relatório de Alunos Matriculados' in s:
                continue
            if 'Agrupado por Responsável Financeiro' in s:
                continue
            if re.match(r'^\d{2}/\d{2}/\d{4}\s+\d{2}:\d{2}', s):
                continue
            if re.match(r'^\d+\s+===$', s):
                continue
            lines.append(s)

    print(f"Total de linhas limpas: {len(lines)}")

    # Identificar blocos de Responsável Financeiro
    # Estrutura típica de cada grupo de Responsável Financeiro:
    # Nome
    # Responsável Financeiro
    # CPF/CNPJ (número)
    # CPF/CNPJ (label)
    # Endereço Completo Telefone(s) (ou Endereço...)
    # Telefones...
    # (Opcional) Responsável Pedagógico:
    #   Nome
    #   Responsável Pedagógico
    #   CPF/CNPJ (número)
    #   ...
    # Matrícula Nome Série / Turma
    # 00001713 LAURA MOREIRA NEVES 8º ANO / A - MAT

    rf_indices = [i for i, l in enumerate(lines) if l == 'Responsável Financeiro']
    print(f"Total de blocos de 'Responsável Financeiro': {len(rf_indices)}")

    grupos = []
    for k in range(len(rf_indices)):
        start_idx = rf_indices[k] - 1  # Linha do nome do responsável financeiro
        end_idx = rf_indices[k+1] - 1 if k + 1 < len(rf_indices) else len(lines)
        bloco_lines = lines[start_idx:end_idx]
        grupos.append(bloco_lines)

    parsed_data = []
    total_alunos_count = 0
    cpfs_rf = set()
    todas_matriculas = set()

    for idx, g in enumerate(grupos):
        # g[0]: Nome RF
        nome_rf = g[0].strip()
        # g[1]: 'Responsável Financeiro'
        # Procurar CPF após 'Responsável Financeiro'
        cpf_rf_raw = ""
        endereco_rf = ""
        telefones_rf = []
        
        # O bloco pode ter Responsável Pedagógico no meio
        tem_pedagogico = False
        pedagogico_start = -1
        for i, line in enumerate(g):
            if line == 'Responsável Pedagógico':
                tem_pedagogico = True
                pedagogico_start = i - 1  # Linha com nome do pedagógico
                break

        # Achar onde começam os alunos: 'Matrícula Nome Série / Turma'
        alunos_start = -1
        for i, line in enumerate(g):
            if 'Matrícula Nome Série / Turma' in line or 'Matrícula' in line and 'Série' in line:
                alunos_start = i
                break

        # Dados do RF estão entre o início e o pedagógico (se houver) ou alunos_start
        rf_end = pedagogico_start if tem_pedagogico else (alunos_start if alunos_start != -1 else len(g))
        rf_lines = g[:rf_end]

        for i, l in enumerate(rf_lines):
            # Encontrar CPF (formato 000.000.000-00 ou 11/14 dígitos)
            cpf_match = re.search(r'\b(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}|\d{11})\b', l)
            if cpf_match and not cpf_rf_raw and i > 0:
                cpf_rf_raw = cpf_match.group(1)
            # Telefones
            if 'Residencial:' in l or 'Celular:' in l or 'Comercial:' in l or 'Telefone(s)' in l:
                # pode estar na própria linha ou na linha seguinte
                telefones_rf.append(l)
            elif re.search(r'\(\d{2}\)\s*\d{4,5}-?\d{4}', l):
                telefones_rf.append(l)

        # Endereço RF: tipicamente após 'CPF/CNPJ' e antes de 'Endereço Completo' ou 'Telefone(s)'
        # Vamos varrer as linhas
        for i, l in enumerate(rf_lines):
            if 'CEP:' in l or 'RUA ' in l.upper() or 'AVENIDA ' in l.upper() or 'FAZENDA ' in l.upper() or 'QD ' in l.upper() or 'SETOR ' in l.upper() or 'CENTRO' in l.upper() or 'VILA ' in l.upper():
                if not re.search(r'\b\d{3}\.\d{3}\.\d{3}-\d{2}\b', l) and l != 'Endereço Completo' and l != 'Endereço Completo Telefone(s)':
                    endereco_rf = l.strip()

        # Dados do Responsável Pedagógico se houver
        dados_pedagogico = None
        if tem_pedagogico:
            ped_end = alunos_start if alunos_start != -1 else len(g)
            ped_lines = g[pedagogico_start:ped_end]
            nome_ped = ped_lines[0].strip()
            cpf_ped_raw = ""
            endereco_ped = ""
            telefones_ped = []
            for i, l in enumerate(ped_lines):
                cpf_match = re.search(r'\b(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}|\d{11})\b', l)
                if cpf_match and not cpf_ped_raw and i > 0:
                    cpf_ped_raw = cpf_match.group(1)
                if 'Residencial:' in l or 'Celular:' in l or 'Comercial:' in l:
                    telefones_ped.append(l)
                elif re.search(r'\(\d{2}\)\s*\d{4,5}-?\d{4}', l):
                    telefones_ped.append(l)
                if 'CEP:' in l or 'RUA ' in l.upper() or 'AVENIDA ' in l.upper() or 'FAZENDA ' in l.upper() or 'QD ' in l.upper() or 'SETOR ' in l.upper():
                    if not re.search(r'\b\d{3}\.\d{3}\.\d{3}-\d{2}\b', l) and 'Endereço' not in l:
                        endereco_ped = l.strip()

            dados_pedagogico = {
                'nome': nome_ped,
                'cpf_formatado': cpf_ped_raw,
                'cpf_limpo': re.sub(r'\D', '', cpf_ped_raw),
                'endereco': endereco_ped,
                'telefones': telefones_ped
            }

        # Extrair Alunos
        alunos = []
        if alunos_start != -1:
            alunos_lines = g[alunos_start + 1:]
            for l in alunos_lines:
                # Formato da linha: 00001713 LAURA MOREIRA NEVES 8º ANO / A - MAT
                m_aluno = re.match(r'^(\d{8})\s+(.+?)\s+((?:MATERNAL|JARDIM|\d+º\s+ANO|\d+ª\s+SÉRIE|ENSINO MÉDIO|NÚCLEO DE APOIO INFANTIL).*?)$', l.strip())
                if m_aluno:
                    mat = m_aluno.group(1)
                    nome_al = m_aluno.group(2).strip()
                    turma_str = m_aluno.group(3).strip()
                    
                    # Decodificar turno: se termina com - MAT é matutino, se - VES é vespertino
                    turno = 'matutino' if re.search(r'-\s*MAT\b', turma_str) else 'vespertino'
                    
                    # Extrair série e turma
                    # Ex: '8º ANO / A - MAT' -> serie: '8º ANO', turma: '8º ANO / A' ou '8º ANO A'
                    turma_limpa = re.sub(r'\s*-\s*(MAT|VES)$', '', turma_str).strip()
                    partes = turma_limpa.split('/')
                    serie = partes[0].strip()
                    turma_letra = partes[1].strip() if len(partes) > 1 else 'A'
                    nome_turma = f"{serie} {turma_letra}"

                    alunos.append({
                        'matricula': mat,
                        'nome': nome_al,
                        'turma_original': turma_str,
                        'serie': serie,
                        'turma_letra': turma_letra,
                        'nome_turma': nome_turma,
                        'turno': turno
                    })
                    total_alunos_count += 1
                    todas_matriculas.add(mat)

        cpf_rf_limpo = re.sub(r'\D', '', cpf_rf_raw)
        cpfs_rf.add(cpf_rf_limpo)

        # Escolher melhor telefone/whatsapp
        whatsapp = ""
        # Buscar celulares primeiro nos telefones
        todos_tels = telefones_rf + (telefones_ped if dados_pedagogico else [])
        for t in todos_tels:
            # Pegar números: (62)99999-9999
            num_match = re.search(r'\(?(\d{2})\)?\s*(\d{4,5})-?(\d{4})', t)
            if num_match:
                whatsapp = f"({num_match.group(1)}) {num_match.group(2)}-{num_match.group(3)}"
                if 'Celular:' in t or num_match.group(2).startswith('9'):
                    break  # celular prioritário

        parsed_data.append({
            'index': idx + 1,
            'responsavel_financeiro': {
                'nome': nome_rf,
                'cpf_formatado': cpf_rf_raw,
                'cpf_limpo': cpf_rf_limpo,
                'endereco': endereco_rf,
                'telefones': telefones_rf,
                'whatsapp': whatsapp
            },
            'responsavel_pedagogico': dados_pedagogico,
            'alunos': alunos
        })

    print(f"\nResumo do Parsing:")
    print(f"- Total de Responsáveis Financeiros agrupados: {len(parsed_data)}")
    print(f"- CPFs únicos de Responsáveis Financeiros: {len(cpfs_rf)}")
    print(f"- Total de Alunos extraídos: {total_alunos_count}")
    print(f"- Matrículas únicas de Alunos: {len(todas_matriculas)}")

    with open('database/matriculas_reais.json', 'w', encoding='utf-8') as f:
        json.dump(parsed_data, f, ensure_ascii=False, indent=2)

    print("Salvo em database/matriculas_reais.json com sucesso!")
    return parsed_data

if __name__ == '__main__':
    parse_pdf_text()

