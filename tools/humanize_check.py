#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Humanize Checker - Anti-AI-Detector Linter
Analisa arquivos Markdown e de documentação para identificar padrões típicos de I.A.:
- Clichês de I.A. (palavras-chave e construções batidas em PT e EN)
- Variação de ritmo (Burstiness / desvio padrão do tamanho das frases)
- Monotonia sintática
- Gera pontuação de 0 a 100% de Humanização
"""

import sys
import re
import math
import os
from pathlib import Path

# Lista de gatilhos frequentes em textos gerados por I.A. (PT e EN)
AI_PATTERNS_PT = [
    (r"\bno cenário atual\b", "Clichê introdutório de I.A."),
    (r"\bem um mundo em constante evolução\b", "Clichê clássico de I.A."),
    (r"\bmergulh(e|ar|ando) fundo\b", "Expressão típica de I.A. ('delve into')"),
    (r"\brobusto ecossistema\b", "Jargão robótico inflado"),
    (r"\borquestraç(ão|ado|ar)\b", "Jargão de I.A. ('orchestrate')"),
    (r"\b(uma|a) tapeçaria de\b", "Tradução literal de I.A. ('tapestry')"),
    (r"\bsintonia perfeita\b", "Clichê poético de I.A."),
    (r"\bcatalisador (de|para)\b", "Metáfora batida de I.A."),
    (r"\bnão apenas [^,\n]+, mas também\b", "Construção simétrica artificial de I.A."),
    (r"\bé fundamental ressaltar\b", "Muleta explicativa de I.A."),
    (r"\bé crucial (notar|ressaltar|lembrar)\b", "Muleta explicativa de I.A."),
    (r"\bem suma\b", "Transição enciclopédica de I.A."),
    (r"\bconcluindo,\b", "Transição escolar de I.A."),
    (r"\bdesvendar os segredos\b", "Frase vazia publicitária"),
    (r"\belevar para o próximo nível\b", "Clichê de marketing de I.A."),
    (r"\brevolucionar a forma como\b", "Exagero corporativo de I.A."),
    (r"\bcom maestria\b", "Adorno artificial de I.A."),
    (r"\bsem atritos?\b", "Jargão corporativo ('frictionless')"),
    (r"\bno cerne (do|da|de)\b", "Tradução literal ('at the core of')"),
    (r"\buma miríade de\b", "Palavra pretensiosa comum em I.A."),
    (r"\bvale destacar que\b", "Muleta repetitiva"),
    (r"\bdesempenha um papel fundamental\b", "Clichê 'pivotal role'"),
]

AI_PATTERNS_EN = [
    (r"\bdelve (into|deeper)\b", "Classic AI trigger word"),
    (r"\btestament to\b", "Common AI cliché"),
    (r"\btapestry of\b", "Frequent AI metaphor"),
    (r"\bseamlessly orchestrat(e|ed|ing)\b", "AI buzzword pair"),
    (r"\bpivotal role\b", "Overused AI phrase"),
    (r"\bbeacon of\b", "AI metaphor"),
    (r"\bin conclusion,\b", "Schoolbook transition"),
    (r"\bnot only [^,\n]+ but also\b", "Rigid AI symmetry"),
    (r"\bembark on a journey\b", "AI cliché opener"),
    (r"\bgame changer\b", "Overused marketing phrase"),
    (r"\bcutting-edge ecosystem\b", "AI buzzword salad"),
    (r"\bat its core\b", "Overused phrase"),
    (r"\ba myriad of\b", "Pretentious AI vocabulary"),
    (r"\bever-evolving landscape\b", "Classic AI cliché"),
    (r"\bit is crucial to (note|highlight)\b", "Common AI filler"),
]

def analyze_text(text: str, filename: str = ""):
    lines = text.splitlines()
    total_words = len(re.findall(r"\b\w+\b", text))
    if total_words == 0:
        return {"file": filename, "score": 100, "warnings": [], "burstiness": 0}

    warnings = []
    # 1. Procurar padrões de I.A.
    combined_patterns = AI_PATTERNS_PT + AI_PATTERNS_EN
    for pattern, reason in combined_patterns:
        for idx, line in enumerate(lines, 1):
            matches = list(re.finditer(pattern, line, re.IGNORECASE))
            for m in matches:
                warnings.append({
                    "line": idx,
                    "match": m.group(0),
                    "reason": reason,
                    "snippet": line.strip()[:90]
                })

    # 2. Medir Burstiness (variação de tamanho das frases)
    sentences = re.split(r"[.!?]+", text)
    sentence_lengths = [len(re.findall(r"\b\w+\b", s)) for s in sentences if len(re.findall(r"\b\w+\b", s)) > 1]

    if len(sentence_lengths) > 3:
        avg_len = sum(sentence_lengths) / len(sentence_lengths)
        variance = sum((l - avg_len) ** 2 for l in sentence_lengths) / len(sentence_lengths)
        std_dev = math.sqrt(variance)
        # Coeficiente de variação (CV = std_dev / avg_len)
        cv = (std_dev / avg_len) if avg_len > 0 else 0
    else:
        cv = 0.5
        avg_len = 10

    # 3. Cálculo da pontuação humana (0 a 100%)
    # - Penalidade por alertas de clichê (cada alerta tira 3 a 5 pontos)
    # - Bônus por alta variação de ritmo (CV > 0.6 é típico de humanos)
    cliche_penalty = min(len(warnings) * 4, 60)
    rhythm_penalty = 0
    if cv < 0.35:
        rhythm_penalty = 20  # Ritmo monótono típico de IA
    elif cv < 0.45:
        rhythm_penalty = 10

    human_score = max(0, min(100, 100 - cliche_penalty - rhythm_penalty))

    return {
        "file": filename,
        "score": human_score,
        "warnings_count": len(warnings),
        "warnings": warnings,
        "total_words": total_words,
        "sentence_count": len(sentence_lengths),
        "burstiness_cv": round(cv, 2)
    }

def print_report(res):
    print(f"\n📄 Arquivo: {res['file']}")
    print(f"   Palavras: {res['total_words']} | Frases: {res['sentence_count']} | Ritmo (Burstiness CV): {res['burstiness_cv']}")
    
    score = res['score']
    if score >= 85:
        badge = "🟢 EXCELENTE (Toque Humano Genuíno)"
    elif score >= 70:
        badge = "🟡 BOM (Pequenos ajustes de clichês recomendados)"
    else:
        badge = "🔴 ALERTA DE I.A. (Muitos clichês e ritmo monótono detectados)"

    print(f"   Nota de Humanização: {score}% — {badge}")
    
    if res['warnings']:
        print(f"   Alertas ({res['warnings_count']}):")
        for w in res['warnings'][:8]:
            print(f"     • Linha {w['line']}: '{w['match']}' -> {w['reason']}")
            print(f"       Contexto: \"{w['snippet']}...\"")
        if res['warnings_count'] > 8:
            print(f"     • ... e mais {res['warnings_count'] - 8} ocorrências.")

def main():
    if len(sys.argv) < 2:
        print("Uso: python tools/humanize_check.py <arquivo_ou_pasta> [<arquivo2> ...]")
        sys.exit(1)

    files_to_check = []
    for arg in sys.argv[1:]:
        target = Path(arg)
        if target.is_file():
            files_to_check.append(target)
        elif target.is_dir():
            for p in target.rglob("*.md"):
                files_to_check.append(p)
        else:
            print(f"Caminho ignorado/inválido: {target}")

    scores = []
    for f in sorted(files_to_check):
        try:
            content = f.read_text(encoding="utf-8", errors="ignore")
            res = analyze_text(content, str(f))
            scores.append(res['score'])
            print_report(res)
        except Exception as e:
            print(f"Erro ao processar {f}: {e}")

    if scores:
        avg_score = sum(scores) / len(scores)
        print(f"\n📊 Média Geral de Humanização: {avg_score:.1f}% em {len(scores)} arquivo(s).\n")

if __name__ == "__main__":
    main()
