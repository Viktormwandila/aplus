import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
const dir=new URL('../public/',import.meta.url);
let scripts=0,links=0;
for(const filename of fs.readdirSync(dir)){
 const text=fs.readFileSync(new URL(filename,dir),'utf8');
 if(filename.endsWith('.js')){new vm.Script(text,{filename});scripts++}
 if(!filename.endsWith('.html'))continue;
 const ids=[...text.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
 if(new Set(ids).size!==ids.length)throw Error('Duplicate ID: '+filename);
 for(const [,script]of text.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)){if(script.trim()){new vm.Script(script,{filename});scripts++}}
 for(const [,href]of text.matchAll(/(?:href|src)="(\/(?!\/)[^"]*)"/g)){
  const name=href.split(/[?#]/)[0].slice(1);const file=new URL(name||'index.html',dir);
  if(!fs.existsSync(file)&&!fs.existsSync(new URL(name+'.html',dir)))throw Error('Broken local link '+filename+': '+href);links++;
 }
 if(text.includes('drive.google.com'))throw Error('Private source link: '+filename);
}
JSON.parse(fs.readFileSync(new URL('../wrangler.jsonc',import.meta.url),'utf8'));
JSON.parse(fs.readFileSync(new URL('manifest.webmanifest',dir),'utf8'));
console.log(`Passed: ${scripts} JavaScript blocks, ${links} local links, unique IDs, manifest and Cloudflare configuration.`);
const practice=fs.readFileSync(new URL('practice.html',dir),'utf8');
const qs=JSON.parse(practice.match(/const qs=([\s\S]*?);\nconst groups=/)[1]);
const groups=JSON.parse(practice.match(/const groups=([\s\S]*?);\nconst \$=/)[1]);
const ids=groups.flatMap(g=>g.ids);
if(ids.length!==qs.length||new Set(ids).size!==qs.length)throw Error('Questions must each belong to exactly one topic');
if(new Set(qs.map(q=>q.diagram)).size!==qs.length)throw Error('Duplicate question diagrams');
for(const g of groups){if(g.ids.length<2)throw Error('Each topic needs multiple questions');for(const i of g.ids){const q=qs[i];if(q.id!==i||q.topicCode!==g.code||q.form!==g.form||q.topic!==g.name||!q.subCode.startsWith(g.code+'.')||!q.competence.startsWith(q.subCode+'.'))throw Error('Invalid syllabus mapping: '+i);if(!q.diagram.includes('<title>')||!q.caption||!q.hint||!q.solution||!Number.isFinite(q.answer))throw Error('Incomplete question: '+i)}}
console.log(`Content passed: ${qs.length} distinct diagrams, ${groups.length} syllabus topics, complete question metadata.`);
