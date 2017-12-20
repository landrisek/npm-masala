import ImportForm from '../react/ImportForm.jsx'
import React from 'react'
import ReactDOM from 'react-dom'

describe('ImportForm', () => {
    it('works', () => {
        var element = document.createElement('div')
        element.id = 'importForm'
        var data = '{"row":{"_prepare-progress":{"Attributes":{"width":0,"id":"_prepare-progress"},"Label":"","Method":"addProgressBar","Validators":[],"Tag":"div"},"_file":{"Attributes":{"id":"_file"},"Label":"P\u0159et\u00e1hn\u011bte sv\u00e9 soubory sem nebo klikn\u011bte dvakr\u00e1t pro vybran\u00ed souboru na disku.","Method":"addUpload","Validators":{"required":{"value":"Nebyl zvolen \u017e\u00e1dn\u00fd soubor pro nahr\u00e1n\u00ed.","style":{"display":"none"}},"text":{"value":"Zvolen\u00fd soubor nen\u00ed platn\u00fd textov\u00fd soubor.","style":{"display":"none"}}},"Tag":"input"},"_submit":{"Attributes":{"className":"btn btn-success","onClick":"submit","type":"submit","id":"_submit"},"Label":"Nahr\u00e1t soubor","Method":"addSubmit","Validators":[],"Tag":"input"},"_prepare":{"Attributes":{"className":"btn btn-success","onClick":"prepare","style":{"display":"none"},"type":"submit","id":"_prepare"},"Label":"Spustit nahr\u00e1v\u00e1n\u00ed","Method":"addSubmit","Validators":[],"Tag":"input"},"_done":{"Attributes":{"style":{"display":"none"},"id":"_done"},"Label":"V\u00e1\u0161 soubor byl nahran\u00fd.","Method":"addMessage","Validators":[],"Tag":"div"}},"validators":[]}'
        element.setAttribute('data', data)
        expect(typeof(document)).toEqual(typeof(element))
        expect(element.id).toEqual('importForm')
        document.body.insertBefore(element, document.getElementById('head'))
        var dom = ReactDOM.render(<ImportForm />, document.getElementById(element.id))
        expect(typeof(dom.state.row['_prepare-progress'])).toEqual('object')
        var doms = dom.attached()
        expect('_prepare-progress').toEqual(doms[0].key)
        var json = JSON.parse(document.querySelector('#importForm').getAttribute('data'))
        expect(typeof(json)).toEqual("object")
        expect(json.row._done.Attributes.id).toEqual('_done')
    });
});