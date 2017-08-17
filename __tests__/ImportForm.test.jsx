import ImportForm from '../react/ImportForm.jsx'
import React from 'react'
import ReactDOM from 'react-dom'

describe('ImportForm', () => {
    it('works', () => {
        var element = document.createElement('div')
        element.id = 'importForm'
        var data = '{"prepare-progress":{"Label":"","Method":"addProgressBar","Attributes":{"width":0,"id":"frm-masala-importform-prepare-progress"},"Validators":[]},"file":{"Label":"P\u0159et\u00e1hn\u011bte sv\u00e9 soubory sem nebo klikn\u011bte dvakr\u00e1t pro vybran\u00ed souboru na disku.","Method":"addUpload","Attributes":{"id":"frm-masala-importform-file"},"Validators":{"required":{"value":"Nebyl zvolen \u017e\u00e1dn\u00fd soubor pro nahr\u00e1n\u00ed.","style":{"display":"none"}},"text":{"value":"Zvolen\u00fd soubor nen\u00ed platn\u00fd textov\u00fd soubor.","style":{"display":"none"}}}},"save":{"Label":"Nahr\u00e1t soubor","Method":"addSubmit","Attributes":{"class":"btn btn-success","onClick":"submit","id":"frm-masala-importform-save"},"Validators":[]},"prepare":{"Label":"Spustit nahr\u00e1v\u00e1n\u00ed","Method":"addSubmit","Attributes":{"class":"btn btn-success","onClick":"prepare","style":{"display":"none"},"id":"frm-masala-importform-prepare"},"Validators":[]},"done":{"Label":"V\u00e1\u0161 soubor byl nahran\u00fd.","Method":"addMessage","Attributes":{"style":{"display":"none"},"id":"frm-masala-importform-done"},"Validators":[]}}'
        element.setAttribute('data', data)
        expect(typeof(document)).toEqual(typeof(element))
        expect(element.id).toEqual('importForm')
        document.body.insertBefore(element, document.getElementById('head'))
        var dom = ReactDOM.render(<ImportForm />, document.getElementById(element.id))
        expect(typeof(dom.state['prepare-progress'])).toEqual('object')
        var doms = dom.attached()
        expect('prepare-progress').toEqual(doms[0].key)
        var json = JSON.parse(document.querySelector('#importForm').getAttribute('data'))
        expect(typeof(json)).toEqual("object")
        expect(json.done.Attributes.id).toEqual('frm-masala-importform-done')
    });
});