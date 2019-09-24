import React from 'react'
import {Autocomplete} from '../app/components/npm-masala/components/Autocomplete.jsx'
import ReactDOM from 'react-dom'

describe('Autocomplete', () => {
    it('works', () => {
        var element = document.createElement('div')
        element.id = 'grid'
        var origin = {id:'Autocomplete'}
        var data = JSON.stringify(origin)
        element.setAttribute('data', data)
        expect('string').toEqual(typeof(data))
    });
});